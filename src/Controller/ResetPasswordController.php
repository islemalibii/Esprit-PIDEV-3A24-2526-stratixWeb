<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $em
    ) {}

    // Étape 1 — Formulaire email
    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function request(Request $request, UtilisateurRepository $repo, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $email = trim($request->request->get('email', ''));
            $user  = $repo->findOneBy(['email' => $email]);

            if ($user) {
                try {
                    $resetToken = $this->resetPasswordHelper->generateResetToken($user);

                    $resetUrl = $this->generateUrl('app_reset_password', ['token' => $resetToken->getToken()], UrlGeneratorInterface::ABSOLUTE_URL);

                    $mail = (new TemplatedEmail())
                        ->from(new Address('noreply@stratix.com', 'Stratix'))
                        ->to($user->getEmail())
                        ->subject('Réinitialisation de votre mot de passe — Stratix')
                        ->htmlTemplate('auth/reset_password_email.html.twig')
                        ->context(['resetToken' => $resetToken, 'user' => $user, 'resetUrl' => $resetUrl]);

                    $mailer->send($mail);
                    $this->setTokenObjectInSession($resetToken);
                } catch (ResetPasswordExceptionInterface $e) {
                    // Debug temporaire — afficher l'erreur
                    $this->addFlash('danger', 'Erreur: ' . $e->getReason());
                }
            }

            return $this->redirectToRoute('app_check_email');
        }

        return $this->render('auth/forgot_password.html.twig');
    }

    // Étape 2 — Page "vérifiez votre email"
    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }
        return $this->render('auth/check_email.html.twig', ['resetToken' => $resetToken]);
    }

    // Étape 3 — Lien du mail → nouveau mot de passe
    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function reset(Request $request, UserPasswordHasherInterface $hasher, string $token = null): Response
    {
        if ($token) {
            $this->storeTokenInSession($token);
            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            return $this->redirectToRoute('app_forgot_password');
        }

        try {
            /** @var Utilisateur $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('danger', 'Lien invalide ou expiré. Veuillez recommencer.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password', '');
            $confirm  = $request->request->get('confirm', '');

            if (strlen($password) < 8) $errors['password'] = 'Minimum 8 caractères.';
            elseif (!preg_match('/[A-Z]/', $password)) $errors['password'] = 'Au moins une majuscule.';
            elseif (!preg_match('/[0-9]/', $password)) $errors['password'] = 'Au moins un chiffre.';
            if ($password && $password !== $confirm) $errors['confirm'] = 'Les mots de passe ne correspondent pas.';

            if (empty($errors)) {
                $this->resetPasswordHelper->removeResetRequest($token);
                $user->setPassword($hasher->hashPassword($user, $password));
                $this->em->flush();
                $this->cleanSessionAfterReset();
                $this->addFlash('success', 'Mot de passe modifié avec succès !');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('auth/reset_new_password.html.twig', ['errors' => $errors]);
    }
}
