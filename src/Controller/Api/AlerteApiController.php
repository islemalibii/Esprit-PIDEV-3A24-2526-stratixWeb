<?php

namespace App\Controller\Api;

use App\Repository\TacheRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/alertes')]
class AlerteApiController extends AbstractController
{
    #[Route('/surcharge', name: 'api_alertes_surcharge', methods: ['GET'])]
    public function surchargeEmployes(TacheRepository $tacheRepository, UtilisateurRepository $utilisateurRepository): JsonResponse
    {
        $taches = $tacheRepository->findAll();
        $employes = $utilisateurRepository->findAll();
        
        $alertes = [];
        foreach ($employes as $e) {
            $nbTaches = count(array_filter($taches, fn($t) => 
                $t->getEmployeId() === $e->getId() && $t->getStatut() !== 'TERMINEE'
            ));
            
            if ($nbTaches >2) {
                $alertes[] = [
                    'employe' => $e->getPrenom() . ' ' . $e->getNom(),
                    'taches_en_cours' => $nbTaches,
                    'niveau' => $nbTaches > 10 ? 'critique' : 'élevé'
                ];
            }
        }
        
        return $this->json([
            'success' => true,
            'alertes' => $alertes,
            'total_alertes' => count($alertes)
        ]);
    }
}