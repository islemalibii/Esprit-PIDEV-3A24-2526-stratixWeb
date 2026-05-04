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
        
        // On force le cast en (string) pour garantir le type attendu par les setters
        $phase->setNom((string)$request->request->get('nom', ''));
        
        $dateDebut = $request->request->get('dateDebut');
        $phase->setDateDebut(new \DateTime(is_string($dateDebut) ? $dateDebut : 'now'));

        $dateFin = $request->request->get('dateFin');
        $phase->setDateFin(new \DateTime(is_string($dateFin) ? $dateFin : 'now'));

        $phase->setObjectif((string)$request->request->get('objectif', ''));
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
        $phase->setNom((string)$request->request->get('nom', ''));

        $dateDebut = $request->request->get('dateDebut');
        $phase->setDateDebut(new \DateTime(is_string($dateDebut) ? $dateDebut : 'now'));

        $dateFin = $request->request->get('dateFin');
        $phase->setDateFin(new \DateTime(is_string($dateFin) ? $dateFin : 'now'));

        $phase->setObjectif((string)$request->request->get('objectif', ''));
        
        if ($request->request->has('statut')) {
            $phase->setStatut((string)$request->request->get('statut', 'En attente'));
        }

        $em->flush();

        $this->addFlash('success', '✅ Phase mise à jour.');
        
        // Correction : Vérifier si le projet existe pour éviter l'erreur sur getId()
        $projet = $phase->getProjet();
        $projetId = $projet ? $projet->getId() : 0;
        
        return $this->redirectToRoute('app_projet_show', ['id' => $projetId]);
    }

    // ─────────────────────────────────────────────
    //  SUPPRIMER UNE PHASE
    // ─────────────────────────────────────────────
    #[Route('/delete/{id}', name: 'app_phase_delete', methods: ['POST'])]
    public function delete(Phase $phase, Request $request, EntityManagerInterface $em): Response
    {
        $projet = $phase->getProjet();
        $projetId = $projet ? $projet->getId() : 0;
        
        
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $phase->getId(), is_string($token) ? $token : '')) {
            $em->remove($phase);
            $em->flush();
            $this->addFlash('success', '🗑️ Phase supprimée avec succès.');
        } else {
            $this->addFlash('danger', '❌ Jeton de sécurité invalide.');
        }

        return $this->redirectToRoute('app_projet_show', ['id' => $projetId]);
    }
}