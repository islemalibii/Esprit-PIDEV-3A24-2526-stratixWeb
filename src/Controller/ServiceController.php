<?php

namespace App\Controller;

use App\Entity\Service;
use App\Entity\CategorieService;
use App\Entity\Utilisateur;
use App\Service\PDFExportService; 
use App\Form\ServiceType;
use App\Repository\ServiceRepository;
use App\Repository\CategorieServiceRepository;
use App\Repository\UtilisateurRepository;
use App\Service\ExchangeRateService;
use App\Service\GroqService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/services')]
final class ServiceController extends AbstractController
{
    #[Route('/', name: 'app_service_index', methods: ['GET'])]
    public function index(Request $request, ServiceRepository $serviceRepository, CategorieServiceRepository $categorieServiceRepository, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search', '');
        $categorie = $request->query->get('categorie', '');
        $archive = $request->query->get('archive', '0') === '1';

        $queryBuilder = $serviceRepository->createQueryBuilder('s')
            ->where('s.archive = :archive')
            ->setParameter('archive', $archive);

        if (!empty($search)) {
            $queryBuilder->andWhere('s.titre LIKE :search OR s.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if (!empty($categorie)) {
            $queryBuilder->leftJoin('s.categorie', 'c')
                ->andWhere('c.nom = :categorie')
                ->setParameter('categorie', $categorie);
        }

        $services = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            6
        );
        
        $items = $services->getItems();
        $itemsArray = is_array($items) ? $items : iterator_to_array($items);
        usort($itemsArray, function($a, $b) {
            return $b->getId() <=> $a->getId();
        });
        $services->setItems($itemsArray);
        
        $now = new \DateTime();
        $sevenDaysAgo = (new \DateTime())->modify('-7 days');
        $newServiceIds = [];
        foreach ($services as $service) {
            $dateCreation = $service->getDateCreation();
            if ($dateCreation && $dateCreation > $sevenDaysAgo) {
                $newServiceIds[] = $service->getId();
            }
        }
        
        $categories = $categorieServiceRepository->findBy(['archive' => false]);

        return $this->render('admin/service/index.html.twig', [
            'services' => $services,
            'categories' => $categories,
            'search' => $search,
            'selectedCategorie' => $categorie,
            'showArchives' => $archive,
            'newServiceIds' => $newServiceIds,  
        ]);
    }

    #[Route('/new', name: 'app_service_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $service = new Service();
        $service->setDateCreation(new \DateTime());
        $service->setArchive(false);

        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($service);
            $entityManager->flush();

            $this->addFlash('success', 'Service créé avec succès.');
            return $this->redirectToRoute('app_service_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/service/new.html.twig', [
            'service' => $service,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_service_show', methods: ['GET'])]
    public function show(Service $service): Response
    {
        return $this->render('admin/service/show.html.twig', [
            'service' => $service,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_service_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Service $service, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ServiceType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Service modifié avec succès.');
            return $this->redirectToRoute('app_service_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/service/edit.html.twig', [
            'service' => $service,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/archive', name: 'app_service_archive', methods: ['POST'])]
    public function archive(Request $request, Service $service, EntityManagerInterface $entityManager): Response
    {
        $token = $request->request->get('_token', '');
        if (is_string($token) && $this->isCsrfTokenValid('archive' . $service->getId(), $token)) {
            $service->setArchive(!$service->isArchive());
            $entityManager->flush();

            $message = $service->isArchive() ? 'Service archivé.' : 'Service restauré.';
            $this->addFlash('success', $message);
        }

        return $this->redirectToRoute('app_service_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_service_delete', methods: ['POST'])]
    public function delete(Request $request, Service $service, EntityManagerInterface $entityManager): Response
    {
        $token = $request->request->get('_token', '');
        if (is_string($token) && $this->isCsrfTokenValid('delete' . $service->getId(), $token)) {
            $entityManager->remove($service);
            $entityManager->flush();

            $this->addFlash('success', 'Service supprimé avec succès.');
        }

        return $this->redirectToRoute('app_service_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/api/exchange-rates', name: 'api_exchange_rates', methods: ['GET'])]
    public function getExchangeRates(ExchangeRateService $exchangeRateService): JsonResponse
    {
        try {
            $usd = $exchangeRateService->convertir(1, 'TND', 'USD');
            $eur = $exchangeRateService->convertir(1, 'TND', 'EUR');
            
            return $this->json([
                'success' => true,
                'base' => 'TND',
                'rates' => [
                    'USD' => $usd,
                    'EUR' => $eur
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/assistant/ask', name: 'api_assistant_ask', methods: ['POST'])]
    public function assistantAsk(Request $request, ServiceRepository $serviceRepository, GroqService $groqService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $question = $data['question'] ?? '';
        
        if (empty($question)) {
            return $this->json(['error' => 'Question vide'], 400);
        }

        try {
            $services = $serviceRepository->findBy(['archive' => false], null, 500);
            $serviceArray = array_values(array_filter($services, function($item) {
                return $item instanceof \App\Entity\Service;
            }));
            $groqService->setServices($serviceArray);
            $response = $groqService->ask($question);
            
            return $this->json(['response' => $response]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/api/search', name: 'app_service_ajax_search', methods: ['GET'])]
    public function ajaxSearch(Request $request, ServiceRepository $serviceRepository, PaginatorInterface $paginator): JsonResponse
    {
        $keyword = $request->query->get('keyword');
        $keyword = is_string($keyword) ? $keyword : null;
        $categorie = $request->query->get('categorie');
        $categorie = is_string($categorie) ? $categorie : null;
        $archive = $request->query->get('archive') === '1';
        $budgetMin = $request->query->get('budgetMin') ? (float) $request->query->get('budgetMin') : null;
        $budgetMax = $request->query->get('budgetMax') ? (float) $request->query->get('budgetMax') : null;
        
        $dateStartValue = $request->query->get('dateStart');
        $dateStart = ($dateStartValue && is_string($dateStartValue)) ? new \DateTime($dateStartValue) : null;
        $dateEndValue = $request->query->get('dateEnd');
        $dateEnd = ($dateEndValue && is_string($dateEndValue)) ? new \DateTime($dateEndValue) : null;
        
        $page = $request->query->getInt('page', 1);
        $limit = 6;

        $queryBuilder = $serviceRepository->getAdvancedSearchQueryBuilder(
            $keyword, $categorie, $archive, $budgetMin, $budgetMax, $dateStart, $dateEnd
        );

        $pagination = $paginator->paginate($queryBuilder, $page, $limit);

        $currentFilters = $request->query->all();
        unset($currentFilters['page']);
        $baseUrl = '/admin/services/api/search?';
        $paginationHtml = $this->renderView('admin/service/_pagination.html.twig', [
            'pagination' => $pagination,
            'baseUrl' => $baseUrl,
            'filters' => $currentFilters,
        ]);

        $services = $pagination->getItems();
        $now = new \DateTime();
        $sevenDaysAgo = (clone $now)->modify('-7 days');

        $data = [];
        foreach ($services as $service) {
            $isNew = $service->getDateCreation() && $service->getDateCreation() > $sevenDaysAgo;
            $data[] = [
                'id'          => $service->getId(),
                'titre'       => $service->getTitre(),
                'budget'      => $service->getBudget(),
                'categorie'   => $service->getCategorie() ? $service->getCategorie()->getNom() : null,
                'description' => $service->getDescription(),
                'dateDebut'   => $service->getDateDebut() ? $service->getDateDebut()->format('d/m/Y') : 'N/A',
                'dateFin'     => $service->getDateFin() ? $service->getDateFin()->format('d/m/Y') : 'N/A',
                'archive'     => $service->isArchive(),
                'isNew'       => $isNew,
            ];
        }

        $pageCount = method_exists($pagination, 'getPageCount') ? $pagination->getPageCount() : 1;

        return $this->json([
            'services' => $data,
            'paginationHtml' => $paginationHtml,
            'total' => $pagination->getTotalItemCount(),
            'currentPage' => $pagination->getCurrentPageNumber(),
            'pageCount' => $pageCount,
        ]);
    }

    #[Route('/api/export-pdf', name: 'api_export_pdf', methods: ['GET'])]
    public function exportPDF(ServiceRepository $serviceRepository, PDFExportService $pdfExportService): Response
    {
        try {
            $services = $serviceRepository->findBy(['archive' => false], null, 500);
            $serviceArray = array_values(array_filter($services, function($item) {
                return $item instanceof \App\Entity\Service;
            }));
            
            $pdfContent = $pdfExportService->exportServicesToPDF($serviceArray, 'Liste des Services');
            
            return new Response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="services_' . date('Y-m-d') . '.pdf"',
            ]);
            
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}