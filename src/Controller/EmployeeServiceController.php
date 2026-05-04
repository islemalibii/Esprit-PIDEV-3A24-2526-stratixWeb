<?php

namespace App\Controller;

use App\Entity\Service;
use App\Repository\ServiceRepository;
use App\Repository\CategorieServiceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\NotificationRepository;
use App\Entity\Utilisateur;

#[Route('/employee/services')]
final class EmployeeServiceController extends AbstractController
{
    #[Route('/', name: 'app_employee_service_index', methods: ['GET'])]
    public function index(Request $request, ServiceRepository $serviceRepository, CategorieServiceRepository $categorieServiceRepository, NotificationRepository $notificationRepository): Response
    {

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
        $search = $request->query->get('search', '');
        $categorie = $request->query->get('categorie', '');

        $queryBuilder = $serviceRepository->createQueryBuilder('s')
            ->leftJoin('s.categorie', 'c')
            ->where('s.archive = false');

        if (!empty($search)) {
            $queryBuilder->andWhere('s.titre LIKE :search OR s.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if (!empty($categorie)) {
            $queryBuilder->andWhere('c.nom = :categorie')
                ->setParameter('categorie', $categorie);
        }

        $services = $queryBuilder->orderBy('s.id', 'DESC')->getQuery()->getResult();
        $categories = $categorieServiceRepository->findBy(['archive' => false]);

        return $this->render('employee/service/index.html.twig', [
            'services' => $services,
            'categories' => $categories,
            'search' => $search,
            'selectedCategorie' => $categorie,
            'showArchives' => false,
            'employee_notifications' => $employeeNotifications,

        ]);
    }

    #[Route('/{id}', name: 'app_employee_service_show', methods: ['GET'])]
    public function show(Service $service, NotificationRepository $notificationRepository): Response
    {
        

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
        if ($service->isArchive()) {
            throw $this->createNotFoundException('Service non disponible.');
        }
        
        return $this->render('employee/service/show.html.twig', [
            'service' => $service,
            'employee_notifications' => $employeeNotifications,

        ]);
    }
}