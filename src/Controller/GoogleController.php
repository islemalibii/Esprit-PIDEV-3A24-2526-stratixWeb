<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\LoginAuthenticator;

class GoogleController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google')]
    public function connect(ClientRegistry $registry): Response
    {
        return $registry->getClient('google')->redirect(['email', 'profile']);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function check(
        Request $request,
        ClientRegistry $registry,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        UserAuthenticatorInterface $authenticator,
        LoginAuthenticator $loginAuthenticator
    ): Response {
        $client = $registry->getClient('google');

        try {
            $googleUser = $client->fetchUser();
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur Google : ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }

        $email = $googleUser->getEmail();
        $user  = $em->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);

        if (!$user) {
            // Créer le compte automatiquement
            $user = new Utilisateur();
            $user->setEmail($email)
                 ->setNom($googleUser->getLastName() ?? 'Google')
                 ->setPrenom($googleUser->getFirstName() ?? 'User')
                 ->setCin(0)
                 ->setRole('employe')
                 ->setStatut('actif')
                 ->setDateAjout(new \DateTime())
                 ->setPassword($hasher->hashPassword($user, bin2hex(random_bytes(16))));

            // Photo Google comme avatar
            $avatar = $googleUser->getAvatar();
            if ($avatar) {
                $user->setAvatar($avatar); // URL externe
            }

            $em->persist($user);
            $em->flush();
        }

        if ($user->isAccountLocked()) {
            $this->addFlash('danger', 'Votre compte est verrouillé.');
            return $this->redirectToRoute('app_login');
        }

        return $authenticator->authenticateUser($user, $loginAuthenticator, $request)
            ?? $this->redirectToRoute('app_emotion_check');
    }
}
