<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/utilisateur')]
class UtilisateurController extends AbstractController
{
    #[Route('/', name: 'utilisateur_index', methods: ['GET'])]
    public function index(UtilisateurRepository $repo, Request $request): Response
    {
        $search = $request->query->get('search', '');
        $role   = $request->query->get('role', '');

        $qb = $repo->createQueryBuilder('u');
        if ($search) {
            $qb->andWhere('u.nom LIKE :s OR u.prenom LIKE :s OR u.email LIKE :s')
               ->setParameter('s', "%$search%");
        }
        if ($role) {
            $qb->andWhere('u.role = :role')->setParameter('role', $role);
        }

        $utilisateurs = $qb->orderBy('u.id', 'DESC')->getQuery()->getResult();

        return $this->render('utilisateur/index.html.twig', [
            'utilisateurs' => $utilisateurs,
            'search'       => $search,
            'role'         => $role,
        ]);
    }

    #[Route('/new', name: 'utilisateur_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $utilisateur = new Utilisateur();
        $form = $this->createForm(UtilisateurType::class, $utilisateur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $utilisateur->setDateAjout(new \DateTime());
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $utilisateur->setPassword(password_hash($plainPassword, PASSWORD_BCRYPT));
            }
            $em->persist($utilisateur);
            $em->flush();
            $this->addFlash('success', 'Utilisateur créé avec succès.');
            return $this->redirectToRoute('utilisateur_index');
        }

        return $this->render('utilisateur/form.html.twig', [
            'form'  => $form,
            'title' => 'Nouvel utilisateur',
        ]);
    }

    #[Route('/{id}/edit', name: 'utilisateur_edit', methods: ['GET', 'POST'])]
    public function edit(Utilisateur $utilisateur, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(UtilisateurType::class, $utilisateur, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $utilisateur->setPassword(password_hash($plainPassword, PASSWORD_BCRYPT));
            }
            $em->flush();
            $this->addFlash('success', 'Utilisateur mis à jour.');
            return $this->redirectToRoute('utilisateur_index');
        }

        return $this->render('utilisateur/form.html.twig', [
            'form'  => $form,
            'title' => 'Modifier l\'utilisateur',
        ]);
    }

    #[Route('/{id}', name: 'utilisateur_show', methods: ['GET'])]
    public function show(Utilisateur $utilisateur): Response
    {
        return $this->render('utilisateur/show.html.twig', [
            'utilisateur' => $utilisateur,
        ]);
    }

    #[Route('/{id}/delete', name: 'utilisateur_delete', methods: ['POST'])]
    public function delete(Utilisateur $utilisateur, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$utilisateur->getId(), (string)$request->request->get('_token'))) {
            $em->remove($utilisateur);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé.');
        }
        return $this->redirectToRoute('utilisateur_index');
    }

    #[Route('/{id}/toggle-lock', name: 'utilisateur_toggle_lock', methods: ['POST'])]
    public function toggleLock(Utilisateur $utilisateur, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('toggle'.$utilisateur->getId(), (string)$request->request->get('_token'))) {
            $utilisateur->setAccountLocked(!$utilisateur->isAccountLocked());
            if (!$utilisateur->isAccountLocked()) {
                $utilisateur->setFailedLoginAttempts(0);
                $utilisateur->setLockedUntil(null);
            }
            $em->flush();
            $this->addFlash('success', 'Statut du compte mis à jour.');
        }
        return $this->redirectToRoute('utilisateur_index');
    }
}
