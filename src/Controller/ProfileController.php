<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/profile')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'admin_profile')]
    public function show(): Response
    {
        return $this->render('admin/profile.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/edit', name: 'admin_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher, ValidatorInterface $validator): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $errors = [];

        if ($request->isMethod('POST')) {
            $nom        = trim((string)$request->request->get('nom', ''));
            $prenom     = trim((string)$request->request->get('prenom', ''));
            $tel        = trim((string)$request->request->get('tel', ''));
            $department = trim((string)$request->request->get('department', ''));
            $poste      = trim((string)$request->request->get('poste', ''));
            $currentPw  = (string)$request->request->get('current_password', '');
            $newPw      = (string)$request->request->get('new_password', '');
            $confirmPw  = (string)$request->request->get('confirm_password', '');

            // Validation
            if (!$nom)    { $errors['nom']    = 'Le nom est obligatoire.'; }
            if (!$prenom) { $errors['prenom'] = 'Le prÃ©nom est obligatoire.'; }
            if ($tel && !preg_match('/^\d{8}$/', $tel)) {
                $errors['tel'] = 'Le tÃ©lÃ©phone doit contenir 8 chiffres.';
            }

            // Changement de mot de passe (optionnel)
            if ($newPw) {
                if (!$hasher->isPasswordValid($user, $currentPw)) {
                    $errors['current_password'] = 'Mot de passe actuel incorrect.';
                } elseif (strlen($newPw) < 8) {
                    $errors['new_password'] = 'Minimum 8 caractÃ¨res.';
                } elseif (!preg_match('/[A-Z]/', $newPw)) {
                    $errors['new_password'] = 'Au moins une majuscule requise.';
                } elseif (!preg_match('/[0-9]/', $newPw)) {
                    $errors['new_password'] = 'Au moins un chiffre requis.';
                } elseif ($newPw !== $confirmPw) {
                    $errors['confirm_password'] = 'Les mots de passe ne correspondent pas.';
                }
            }

            if (empty($errors)) {
                $user->setNom($nom)
                     ->setPrenom($prenom)
                     ->setTel($tel ?: null)
                     ->setDepartment($department ?: null)
                     ->setPoste($poste ?: null);

                if ($newPw) {
                    $user->setPassword($hasher->hashPassword($user, $newPw));
                }

                $em->flush();
                $this->addFlash('success', 'Profil mis Ã  jour avec succÃ¨s.');
                return $this->redirectToRoute('admin_profile');
            }
        }

        return $this->render('admin/profile_edit.html.twig', [
            'user'   => $user,
            'errors' => $errors,
        ]);
    }
}