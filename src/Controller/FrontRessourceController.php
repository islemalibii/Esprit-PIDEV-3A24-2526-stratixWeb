<?php

namespace App\Controller;

use App\Repository\RessourceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\NotificationRepository;
use App\Entity\Utilisateur;

class FrontRessourceController extends AbstractController
{
    #[Route('/catalogue-ressources', name: 'front_ressource_index')]
    public function index(RessourceRepository $repository, Request $request, NotificationRepository $notificationRepository): Response
    {
        // Correction : Utilisation de getString() pour garantir un type string à PHPStan
        $searchTerm = $request->query->getString('q');

        if ($searchTerm !== '') {
            // Désormais $searchTerm est garanti d'être une string
            $ressources = $repository->findBySearch($searchTerm);
        } else {
            // Tri par nom ascendant pour une meilleure lisibilité
            $ressources = $repository->findBy([], ['nom' => 'ASC']);
        }

        // Calcul des statistiques pour la barre du bas
        $total = count($ressources);
        $quantiteTotale = 0;
        
        foreach ($ressources as $r) {
            // On additionne les quantités de chaque ressource
            $quantiteTotale += $r->getQuantite();
        }

        $employe = $this->getUser();
        if (!$employe instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }
        $employeeNotifications = [];
        $notifications = $notificationRepository->findBy(
            ['userId' => $employe->getId(), 'isRead' => false],
            ['createdAt' => 'DESC'],
            5
        );
        foreach ($notifications as $notif) {
            $employeeNotifications[] = [
                'title' => $notif->getTitle(),
                'message' => $notif->getMessage(),
                'date' => $notif->getCreatedAt(),
                'color' => '#3b82f6',
                'icon' => 'ti-bell',

            ];
        }

        // Rendu vers le template employé (Stratix)
        return $this->render('employee/ressource/index.html.twig', [
            'ressources' => $ressources,
            'searchTerm' => $searchTerm,
            'stats' => [
                'total' => $total,
                'quantiteTotale' => $quantiteTotale
            ],
            'employee_notifications' => $employeeNotifications,

        ]);
    }
}