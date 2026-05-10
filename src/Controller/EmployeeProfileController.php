<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/employee/profile')]
class EmployeeProfileController extends AbstractController
{
    #[Route('', name: 'employee_profile')]
    public function show(): Response
    {
        return $this->render('employee/profile.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/edit', name: 'employee_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $errors = [];

        if ($request->isMethod('POST')) {
            $nom       = trim((string)$request->request->get('nom', ''));
            $prenom    = trim((string)$request->request->get('prenom', ''));
            $tel       = trim((string)$request->request->get('tel', ''));
            $currentPw = (string)$request->request->get('current_password', '');
            $newPw     = (string)$request->request->get('new_password', '');
            $confirmPw = (string)$request->request->get('confirm_password', '');

            if (!$nom)    $errors['nom']    = 'Le nom est obligatoire.';
            if (!$prenom) $errors['prenom'] = 'Le prÃ©nom est obligatoire.';
            if ($tel && !preg_match('/^\d{8}$/', $tel)) {
                $errors['tel'] = 'Le tÃ©lÃ©phone doit contenir 8 chiffres.';
            }

            if ($newPw) {
                if (!$hasher->isPasswordValid($user, $currentPw)) {
                    $errors['current_password'] = 'Mot de passe actuel incorrect.';
                } elseif (strlen($newPw) < 8 ){
                    $errors['new_password'] = 'Minimum 8 caractéres.';
                } elseif (!preg_match('/[A-Z]/', $newPw)) {
                    $errors['new_password'] = 'Au moins une majuscule requise.';
                } elseif (!preg_match('/[0-9]/', $newPw)) {
                    $errors['new_password'] = 'Au moins un chiffre requis.';
                } elseif ($newPw !== $confirmPw) {
                    $errors['confirm_password'] = 'Les mots de passe ne correspondent pas.';
                }
            }

            if (empty($errors)) {
                $user->setNom($nom)->setPrenom($prenom)->setTel($tel ?: null);
                if ($newPw) {
                    $user->setPassword($hasher->hashPassword($user, $newPw));
                }
                $em->flush();
                $this->addFlash('success', 'Profil mis Ã  jour.');
                return $this->redirectToRoute('employee_profile');
            }
        }

        return $this->render('employee/profile_edit.html.twig', [
            'user'   => $user,
            'errors' => $errors,
        ]);
    }
}