<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontProduitController extends AbstractController
{
    #[Route('/boutique', name: 'front_produit_index')]
    public function index(ProduitRepository $repository, Request $request): Response
    {
        // Récupération du terme de recherche
        $searchTerm = $request->query->get('q', '');

        // Utilisation du tri par défaut (Nom croissant)
        if ($searchTerm) {
            $produits = $repository->findBySearch($searchTerm);
        } else {
            // On trie par nom pour que la liste soit plus lisible
            $produits = $repository->findBy([], ['nom' => 'ASC']);
        }

        // Statistiques
        $total = count($produits);
        $disponibles = 0;
        
        foreach ($produits as $p) {
            // Un produit est disponible si son stock est > 0
            if ($p->getStockActuel() > 0) {
                $disponibles++;
            }
        }

        return $this->render('employee/produit/index.html.twig', [
            'produits' => $produits,
            'searchTerm' => $searchTerm,
            'stats' => [
                'total' => $total,
                'disponibles' => $disponibles
            ]
        ]);
    }
}