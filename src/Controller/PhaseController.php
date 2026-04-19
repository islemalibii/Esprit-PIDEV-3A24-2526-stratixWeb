<?php

namespace App\Controller;

use App\Entity\Phase;
use App\Entity\Projet;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/phase')]
class PhaseController extends AbstractController
{
    // ─────────────────────────────────────────────
    //  AJOUTER UNE PHASE À UN PROJET
    // ─────────────────────────────────────────────
    #[Route('/new/{id}', name: 'app_phase_new', methods: ['POST'])]
    public function new(Projet $projet, Request $request, EntityManagerInterface $em): Response
    {
        $phase = new Phase();
        
        // Récupération des données du formulaire (en supposant l'usage de requêtes POST directes)
        $phase->setNom($request->request->get('nom'));
        $phase->setDateDebut(new \DateTime($request->request->get('dateDebut')));
        $phase->setDateFin(new \DateTime($request->request->get('dateFin')));
        $phase->setObjectif($request->request->get('objectif'));
        $phase->setStatut('En attente');
        
        // Liaison avec le projet STRATIX
        $phase->setProjet($projet);

        $em->persist($phase);
        $em->flush();

        $this->addFlash('success', '✅ Phase "' . $phase->getNom() . '" ajoutée avec succès au projet !');
        
        return $this->redirectToRoute('app_projet_show', ['id' => $projet->getId()]);
    }

    // ─────────────────────────────────────────────
    //  MODIFIER UNE PHASE
    // ─────────────────────────────────────────────
    #[Route('/edit/{id}', name: 'app_phase_edit', methods: ['POST'])]
    public function edit(Phase $phase, Request $request, EntityManagerInterface $em): Response
    {
        $phase->setNom($request->request->get('nom'));
        $phase->setDateDebut(new \DateTime($request->request->get('dateDebut')));
        $phase->setDateFin(new \DateTime($request->request->get('dateFin')));
        $phase->setObjectif($request->request->get('objectif'));
        
        // Optionnel : mise à jour du statut si présent dans la requête
        if ($request->request->has('statut')) {
            $phase->setStatut($request->request->get('statut'));
        }

        $em->flush();

        $this->addFlash('success', '✅ Phase mise à jour.');
        
        return $this->redirectToRoute('app_projet_show', ['id' => $phase->getProjet()->getId()]);
    }

    // ─────────────────────────────────────────────
    //  SUPPRIMER UNE PHASE
    // ─────────────────────────────────────────────
    #[Route('/delete/{id}', name: 'app_phase_delete', methods: ['POST'])]
    public function delete(Phase $phase, Request $request, EntityManagerInterface $em): Response
    {
        $projetId = $phase->getProjet()->getId();
        
        // Vérification du jeton CSRF pour la sécurité
        if ($this->isCsrfTokenValid('delete' . $phase->getId(), $request->request->get('_token'))) {
            $em->remove($phase);
            $em->flush();
            $this->addFlash('success', '🗑️ Phase supprimée avec succès.');
        } else {
            $this->addFlash('danger', '❌ Jeton de sécurité invalide.');
        }

        return $this->redirectToRoute('app_projet_show', ['id' => $projetId]);
    }
}