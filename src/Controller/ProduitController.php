<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use App\Service\PdfService; 
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
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
        $sortBy = $request->query->get('sort', 'nom');
        $direction = $request->query->get('direction', 'asc');

        $produits = $searchTerm 
            ? $repository->findBySearch($searchTerm) 
            : $repository->findBy([], [$sortBy => $direction]);

        $stats = [
            'total' => count($produits),
            'stockFaible' => 0,
            'valeurStock' => 0,
        ];

        foreach ($produits as $p) {
            if ($p->getStockActuel() <= $p->getStockMin()) {
                $stats['stockFaible']++;
            }
            $stats['valeurStock'] += ($p->getPrix() * $p->getStockActuel());
        }

        return $this->render('admin/produit/index.html.twig', [
            'produits' => $produits,
            'searchTerm' => $searchTerm,
            'stats' => $stats
        ]);
    }

    /**
     * Formulaire unifié pour l'ajout et la modification
     */
    #[Route('/produit/new', name: 'produit_new')]
    #[Route('/produit/edit/{id}', name: 'produit_edit')]
    public function form(?Produit $produit = null, Request $request, EntityManagerInterface $em): Response
    {
        $editMode = ($produit !== null);
        $aujourdhui = new \DateTime('today');

        if (!$produit) {
            $produit = new Produit();
            $produit->setDateCreation(new \DateTime()); 
        }

        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            // Validation personnalisée de la date de fabrication
            if (!$editMode && $produit->getDateFabrication() < $aujourdhui) {
                $this->addFlash('error', 'La date de fabrication ne peut pas être antérieure à aujourd\'hui.');
                return $this->render('admin/produit/formulaire.html.twig', [
                    'form' => $form->createView(),
                    'editMode' => $editMode,
                    'produit' => $produit // Ajout indispensable ici
                ]);
            }

            // Gestion de l'upload de l'image
            $imageFile = $form->get('image_file')->getData();
            if ($imageFile) {
                $newFilename = 'produit_'.uniqid().'.'.$imageFile->guessExtension();
                try {
                    $imageFile->move(
                        $this->getParameter('produits_images_directory'), 
                        $newFilename
                    );
                    $produit->setImagePath($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', "Erreur lors de l'enregistrement de l'image sur le serveur.");
                }
            }

            $em->persist($produit);
            $em->flush();
            
            $this->addFlash('success', $editMode ? 'Le produit a été mis à jour.' : 'Le produit a été ajouté à l\'inventaire Stratix.');
            return $this->redirectToRoute('produit_index');
        }

        return $this->render('admin/produit/formulaire.html.twig', [
            'form' => $form->createView(),
            'editMode' => $editMode,
            'produit' => $produit // Ajout indispensable ici pour le template
        ]);
    }

    /**
     * Suppression sécurisée (Token CSRF)
     */
    #[Route('/produit/delete/{id}', name: 'produit_delete', methods: ['POST'])]
    public function delete(Produit $produit, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->request->get('_token'))) {
            $em->remove($produit);
            $em->flush();
            $this->addFlash('success', 'Le produit a été supprimé.');
        }
        
        return $this->redirectToRoute('produit_index');
    }

    /**
     * Génération du PDF pour l'inventaire
     */
    #[Route('/produit/pdf', name: 'produit_pdf')]
    public function generatePdf(ProduitRepository $repository, PdfService $pdf): Response
    {
        $produits = $repository->findAll();
        $html = $this->renderView('admin/produit/pdf.html.twig', [
            'produits' => $produits
        ]);
        
        return $pdf->showPdfFile($html, 'Inventaire_Stratix_' . date('Y-m-d'));
    }

    // --- SECTION API (JSON) ---

    /**
     * API : Liste des produits pour Flutter
     */
    #[Route('/api/produits', name: 'api_produits_list', methods: ['GET'])]
    public function apiIndex(ProduitRepository $repository): JsonResponse
    {
        $produits = $repository->findAll();
        $data = array_map(function($p) {
            return [
                'id' => $p->getId(),
                'nom' => $p->getNom(),
                'prix' => $p->getPrix(),
                'stock' => $p->getStockActuel(),
                'image' => $p->getImagePath() ? '/uploads/produits/' . $p->getImagePath() : null,
            ];
        }, $produits);

        return $this->json([
            'status' => 'success',
            'count' => count($data),
            'produits' => $data
        ]);
    }

    /**
     * API : Export PDF en Base64
     */
    #[Route('/api/produit/pdf', name: 'api_produit_pdf', methods: ['GET'])]
    public function apiPdf(ProduitRepository $repository, PdfService $pdf): JsonResponse
    {
        $produits = $repository->findAll();
        $html = $this->renderView('admin/produit/pdf.html.twig', [
            'produits' => $produits
        ]);
        
        $binary = $pdf->getBinaryContent($html);

        return $this->json([
            'status' => 'success',
            'filename' => 'Rapport_Stratix_API.pdf',
            'base64' => base64_encode($binary)
        ]);
    }
}