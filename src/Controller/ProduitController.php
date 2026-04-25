<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use App\Service\GrokService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ProduitController extends AbstractController
{
    /**
     * Liste des produits avec recherche et statistiques
     */
    #[Route('/produit', name: 'produit_index')]
    public function index(ProduitRepository $repository, Request $request): Response
    {
        $searchTerm = $request->query->get('q', '');
        $produits = $searchTerm ? $repository->findBySearch($searchTerm) : $repository->findAll();

        $stats = ['total' => count($produits), 'stockFaible' => 0, 'valeurStock' => 0];
        foreach ($produits as $p) {
            if ($p->getStockActuel() <= $p->getStockMin()) $stats['stockFaible']++;
            $stats['valeurStock'] += ($p->getPrix() * $p->getStockActuel());
        }

        return $this->render('admin/produit/index.html.twig', [
            'produits' => $produits,
            'searchTerm' => $searchTerm,
            'stats' => $stats
        ]);
    }

    /**
     * Assistant IA utilisant le service centralisé GrokService
     */
    #[Route('/produit/chat', name: 'produit_chat', methods: ['POST'])]
    public function chatIA(Request $request, ProduitRepository $repository, GrokService $grok): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $question = $data['question'] ?? '';

        if (empty($question)) {
            return $this->json(['reponse' => "Veuillez poser une question."]);
        }

        // On récupère les données de l'inventaire
        $produits = $repository->findAll();
        
        try {
            /** * On appelle la méthode repondreQuestionStock du service.
             * Elle fonctionnera aussi pour les produits car elle prend un tableau d'objets.
             */
            $aiResponse = $grok->repondreQuestionStock($question, $produits);
        } catch (\Exception $e) {
            $aiResponse = "Erreur via GrokService : " . $e->getMessage();
        }

        return $this->json(['reponse' => $aiResponse]);
    }

    /**
     * Création d'un nouveau produit
     */
    #[Route('/produit/new', name: 'produit_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($produit);
            $entityManager->flush();
            $this->addFlash('success', 'Produit ajouté avec succès !');
            return $this->redirectToRoute('produit_index');
        }

        return $this->render('admin/produit/new.html.twig', [
            'produit' => $produit,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modification d'un produit
     */
    #[Route('/produit/{id}/edit', name: 'produit_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Produit mis à jour !');
            return $this->redirectToRoute('produit_index');
        }

        return $this->render('admin/produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Suppression d'un produit
     */
    #[Route('/produit/{id}', name: 'produit_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $produit->getId(), $request->request->get('_token'))) {
            $entityManager->remove($produit);
            $entityManager->flush();
            $this->addFlash('success', 'Produit supprimé.');
        }

        return $this->redirectToRoute('produit_index');
    }

    /**
     * Export PDF
     */
    #[Route('/produit/export/pdf', name: 'produit_pdf')]
    public function exportPdf(): Response
    {
        // Tu peux injecter ton PdfService ici si nécessaire
        return new Response("Exportation PDF...");
    }
}