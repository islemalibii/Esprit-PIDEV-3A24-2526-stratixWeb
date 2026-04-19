<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Service\RecaptchaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AuthController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('app_emotion_check');
        }
        if (in_array($user->getRole(), ['responsable_rh', 'responsable_projet', 'responsable_production', 'ceo'])) {
            return $this->redirectToRoute('app_emotion_check');
        }
        return $this->redirectToRoute('app_emotion_check');
    }

    #[Route('/emotion-check', name: 'app_emotion_check')]
    public function emotionCheck(): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        return $this->render('auth/emotion_check.html.twig');
    }

    #[Route('/emotion-redirect', name: 'app_emotion_redirect')]
    public function emotionRedirect(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('admin_dashboard');
        }
        if (in_array($user->getRole(), ['responsable_rh', 'responsable_projet', 'responsable_production', 'ceo'])) {
            return $this->redirectToRoute('admin_dashboard');
        }
        return $this->redirectToRoute('app_employee_dashboard');
    }

    #[Route('/theme/toggle', name: 'app_theme_toggle', methods: ['POST'])]
    public function toggleTheme(Request $request, EntityManagerInterface $em): \Symfony\Component\HttpFoundation\JsonResponse
    {
        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non connecté'], 401);
        }
        $theme = $request->request->get('theme', 'light');
        $user->setTheme(in_array($theme, ['light', 'dark']) ? $theme : 'light');
        $em->flush();
        return $this->json(['theme' => $user->getTheme()]);
    }

    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        return $this->render('auth/login.html.twig', [
            'last_username' => $authUtils->getLastUsername(),
            'error'         => $authUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void {}

    #[Route('/signup', name: 'app_signup', methods: ['GET', 'POST'])]
    public function signup(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        RecaptchaService $recaptcha
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            // Vérification reCAPTCHA
            $recaptchaToken = $request->request->get('recaptcha_token', '');
            if ($recaptchaToken && !$recaptcha->isHuman($recaptchaToken)) {
                $errors['recaptcha'] = 'Activité suspecte détectée. Veuillez réessayer.';
            }

            $nom      = trim($request->request->get('nom', ''));
            $prenom   = trim($request->request->get('prenom', ''));
            $email    = trim($request->request->get('email', ''));
            $cin      = trim($request->request->get('cin', ''));
            $password = $request->request->get('password', '');
            $confirm  = $request->request->get('confirm', '');

            if (!$nom)    $errors['nom']    = 'Le nom est obligatoire.';
            elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s\-]+$/', $nom)) $errors['nom'] = 'Lettres uniquement.';

            if (!$prenom) $errors['prenom'] = 'Le prénom est obligatoire.';
            elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s\-]+$/', $prenom)) $errors['prenom'] = 'Lettres uniquement.';

            if (!$email)  $errors['email']  = "L'email est obligatoire.";
            elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email invalide.';
            elseif ($em->getRepository(Utilisateur::class)->findOneBy(['email' => $email])) $errors['email'] = 'Cet email est déjà utilisé.';

            if (!$cin)    $errors['cin']    = 'Le CIN est obligatoire.';
            elseif (!preg_match('/^\d{8}$/', $cin)) $errors['cin'] = 'Le CIN doit contenir exactement 8 chiffres.';

            if (!$password) $errors['password'] = 'Le mot de passe est obligatoire.';
            elseif (strlen($password) < 8) $errors['password'] = 'Minimum 8 caractères.';
            elseif (!preg_match('/[A-Z]/', $password)) $errors['password'] = 'Au moins une majuscule requise.';
            elseif (!preg_match('/[0-9]/', $password)) $errors['password'] = 'Au moins un chiffre requis.';

            if ($password && $password !== $confirm) $errors['confirm'] = 'Les mots de passe ne correspondent pas.';

            // Validation photo : face_validated doit être "1" si une photo est uploadée
            /** @var UploadedFile|null $avatarFile */
            $avatarFile = $request->files->get('avatar');
            if ($avatarFile && $request->request->get('face_validated') !== '1') {
                $errors['avatar'] = 'Aucun visage humain détecté. Veuillez uploader une photo avec votre visage visible.';
            }

            if (empty($errors)) {
                $user = new Utilisateur();
                $user->setNom($nom)
                     ->setPrenom($prenom)
                     ->setEmail($email)
                     ->setCin((int)$cin)
                     ->setRole('employe')
                     ->setStatut('actif')
                     ->setDateAjout(new \DateTime())
                     ->setPassword($hasher->hashPassword($user, $password));

                // Sauvegarde de la photo si uploadée
                if ($avatarFile) {
                    $filename = uniqid('avatar_') . '.' . $avatarFile->guessExtension();
                    $avatarFile->move($this->getParameter('kernel.project_dir') . '/public/images/avatar', $filename);
                    $user->setAvatar($filename);
                }

                $em->persist($user);
                $em->flush();

                // Invalider la session pour ne pas connecter automatiquement
                $request->getSession()->invalidate();

                $this->addFlash('success', 'Compte créé ! Vous pouvez vous connecter.');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('auth/signup.html.twig', [
            'errors' => $errors,
            'old'    => $request->request->all(),
        ]);
    }
}
