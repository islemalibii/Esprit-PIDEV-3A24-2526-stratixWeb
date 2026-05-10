<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
// La ligne NotificationRepository a été supprimée d'ici
use App\Entity\Utilisateur;

class FrontProduitController extends AbstractController
{
    #[Route('/boutique', name: 'front_produit_index')]
    public function index(ProduitRepository $repository, Request $request): Response
    {
        // On a retiré l'argument $notificationRepository pour stopper l'erreur d'autowiring
        
        $searchTerm = $request->query->getString('q');

        if ($searchTerm !== '') {
            $produits = $repository->findBySearch($searchTerm);
        } else {
            $produits = $repository->findBy([], ['nom' => 'ASC']);
        }

        $total = count($produits);
        $disponibles = 0;
        
        foreach ($produits as $p) {
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