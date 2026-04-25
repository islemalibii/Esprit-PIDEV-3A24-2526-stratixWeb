<?php

namespace App\Controller;

use App\Repository\ServiceRepository;
use App\Entity\CategorieService;
use App\Form\CategorieServiceType;
use App\Repository\CategorieServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/categories')]
final class CategorieServiceController extends AbstractController
{
    #[Route('/', name: 'app_categorie_service_index', methods: ['GET'])]
    public function index(Request $request, CategorieServiceRepository $categorieServiceRepository, PaginatorInterface $paginator, ServiceRepository $serviceRepository): Response
    {
        $search = $request->query->get('search', '');
        $archive = $request->query->get('archive', '0') === '1';

        $queryBuilder = $categorieServiceRepository->createQueryBuilder('c')
            ->leftJoin('c.services', 's')
            ->addSelect('s')
            ->where('c.archive = :archive')
            ->setParameter('archive', $archive);

        if (!empty($search)) {
            $queryBuilder->andWhere('c.nom LIKE :search OR c.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $queryBuilder->orderBy('c.nom', 'ASC');
        
        $categories = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            6
        );

        $totalServices = $serviceRepository->count(['archive' => false]);

        return $this->render('admin/categorie_service/index.html.twig', [
            'categorie_services' => $categories,
            'search' => $search,
            'showArchives' => $archive,
            'totalServices' => $totalServices,
        ]);
    }

    #[Route('/new', name: 'app_categorie_service_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $categorieService = new CategorieService();
        $categorieService->setDateCreation(new \DateTime());
        $categorieService->setArchive(false);

        $form = $this->createForm(CategorieServiceType::class, $categorieService);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($categorieService);
            $entityManager->flush();

            $this->addFlash('success', 'Catégorie créée avec succès.');
            return $this->redirectToRoute('app_categorie_service_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/categorie_service/new.html.twig', [
            'categorie_service' => $categorieService,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_categorie_service_show', methods: ['GET'])]
    public function show(CategorieService $categorieService): Response
    {
        return $this->render('admin/categorie_service/show.html.twig', [
            'categorie_service' => $categorieService,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_categorie_service_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CategorieService $categorieService, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategorieServiceType::class, $categorieService);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Catégorie modifiée avec succès.');
            return $this->redirectToRoute('app_categorie_service_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/categorie_service/edit.html.twig', [
            'categorie_service' => $categorieService,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/archive', name: 'app_categorie_service_archive', methods: ['POST'])]
    public function archive(Request $request, CategorieService $categorieService, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('archive'.$categorieService->getId(), $request->request->get('_token'))) {
            $categorieService->setArchive(!$categorieService->isArchive());
            $entityManager->flush();

            $message = $categorieService->isArchive() ? 'Catégorie archivée.' : 'Catégorie restaurée.';
            $this->addFlash('success', $message);
        }

        return $this->redirectToRoute('app_categorie_service_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_categorie_service_delete', methods: ['POST'])]
    public function delete(Request $request, CategorieService $categorieService, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$categorieService->getId(), $request->request->get('_token'))) {
            $entityManager->remove($categorieService);
            $entityManager->flush();

            $this->addFlash('success', 'Catégorie supprimée avec succès.');
        }

        return $this->redirectToRoute('app_categorie_service_index', [], Response::HTTP_SEE_OTHER);
    }
}