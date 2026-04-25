<?php

namespace App\Controller;
use App\Entity\Evenement;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use App\Repository\EventFeedbackRepository;
use App\Repository\ParticipationRepository;
use App\Service\PictureService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Snappy\Pdf;
use App\Service\MeetingSummaryService;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\RecurrenceService;
use App\Service\RecommendationService;


class EventController extends AbstractController
{
    //back office
    #[Route('/responsable/evenement', name: 'resp_event_index')]
    public function responsableIndex(Request $request, EvenementRepository $repo, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search');
        $type_event = $request->query->get('type_event');

        if ($search) {
            $events = $repo->searchByTitle($search);
        } elseif ($type_event) {
            $events = $repo->filterByType($type_event);
        } else {
            $events = $repo->findByArchiveStatus(false);
        }
        $pagination = $paginator->paginate(
            $events,
            $request->query->getInt('page', 1), 
            10
        );

        return $this->render('admin/events/responsableEvent.html.twig', [
            'evenements' => $pagination,
            'archived' => $repo->findByArchiveStatusArray(true)
        ]);
    }

    #[Route('/responsable/evenement/archives', name: 'resp_event_archives')]
    public function archives(EvenementRepository $repo): Response
    {
        return $this->render('admin/events/archivedEvents.html.twig', [
            'archived' => $repo->findByArchiveStatusArray(true)
        ]);
    }
    #[Route('/responsable/evenement/new', name: 'resp_event_new')]
    #[Route('/responsable/evenement/edit/{id}', name: 'resp_event_edit')]
    public function save(Evenement $evenement = null, Request $request, EntityManagerInterface $em, PictureService $pictureService, RecurrenceService $recurrenceService): Response
    {
        if (!$evenement) $evenement = new Evenement();

        $isNew = !$evenement->getId(); 
        $form = $this->createForm(EvenementType::class, $evenement, [
            'validation_groups' => $isNew ? ['Default', 'create'] : ['Default', 'edit'],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($evenement->isArchived() === null) {
                $evenement->setIsArchived(false);
            }
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $imageUrl = $pictureService->uploadImage($imageFile);
                $evenement->setImageUrl($imageUrl);
            }

            $em->persist($evenement);
            $em->flush();
            if ($isNew) {
                $recurrenceService->generateRecurringEvents($evenement);
            }
            return $this->redirectToRoute('resp_event_index');
        }

        return $this->render('admin/events/formEvent.html.twig', [
            'form' => $form->createView(),
            'evenement' => $evenement
        ]);
    }

    
    #[Route('/responsable/evenement/{id}', name: 'resp_event_show')]
    public function show(Evenement $evenement, EventFeedbackRepository $feedbackRepo): Response
    {
        return $this->render('admin/events/showEvent.html.twig', [
            'evenement' => $evenement,
            'feedbacks' => $feedbackRepo->findBy(['evenement' => $evenement]),
        ]);
    }

    #[Route('/responsable/evenement/archive/{id}', name: 'resp_event_archive')]
    public function archive(Evenement $evenement, EntityManagerInterface $em): Response
    {
        $evenement->setIsArchived(!$evenement->isIsArchived());
        $em->flush();
        return $this->redirectToRoute('resp_event_index');
    }


    #[Route('/responsable/evenement/{id}/pdf', name: 'resp_event_pdf')]
    public function exportPdf(Evenement $evenement, Pdf $pdf, EventFeedbackRepository $feedbackRepo): Response
    {
        $html = $this->renderView('admin/events/pdf.html.twig', [
            'evenement' => $evenement,
            'feedbacks' => $feedbackRepo->findBy(['evenement' => $evenement]),
        ]);

        return new Response(
            $pdf->getOutputFromHtml($html),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="event-' . $evenement->getId() . '.pdf"',
            ]
        );
    }

    #[Route('/responsable/evenement/{id}/summary', name: 'resp_event_summary', methods: ['POST'])]
    public function generateSummary(
        Evenement $evenement,
        EventFeedbackRepository $feedbackRepo,
        MeetingSummaryService $summaryService
    ): JsonResponse {
        $feedbacks = $feedbackRepo->findBy(['evenement' => $evenement]);

        if (empty($feedbacks)) {
            return $this->json([
                'success' => false,
                'message' => 'Aucun avis disponible pour générer un résumé.'
            ]);
        }
        $summary = $summaryService->generateSummary($feedbacks, $evenement->getTitre());
        if (!$summary) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du résumé.'
            ]);
        }
        return $this->json([
            'success' => true,
            'summary' => $summary
        ]);
    }

    
    // front office
    #[Route('/employee/events', name: 'emp_event_list')]
    public function employeeIndex(Request $request, EvenementRepository $repo, ParticipationRepository $participationRepo, EventFeedbackRepository $feedbackRepo, PaginatorInterface $paginator, RecommendationService $recommendationService): Response
    {
        $type   = $request->query->get('type');
        $search = $request->query->get('search');

        if ($search) {
            $events = $repo->searchPlanifierByTitle($search);
        } elseif ($type) {
            $events = $repo->filterByTypeForEmployee($type);
        } else {
            $events = $repo->findVisibleForEmployees();
        }
        $pagination = $paginator->paginate(
            $events,
            $request->query->getInt('page', 1),
            9
        );

        $userEmail = $this->getUser()?->getUserIdentifier();

        $joinedEventIds = $participationRepo->findUserEventIds($userEmail);

        $recommendations = $recommendationService->getRecommendations($userEmail);
        dump($recommendations);
        dump($userEmail);

        return $this->render('employee/events/list.html.twig', [
            'events'          => $pagination,
            'userEmail'     => $userEmail,
            'joinedEventIds'=> $joinedEventIds, 
            'feedbackEventIds'=> $feedbackRepo->findFeedbackEventIds($userEmail),
            'recommendations'  => $recommendations,
        ]);
    }
    #[Route('/event/{id}', name: 'emp_event_show', methods: ['GET'])]
    public function showDEpmloyee(Evenement $evenement, ParticipationRepository $participationRepo): Response
    {
        $userEmail      = $this->getUser()?->getUserIdentifier();
        $alreadyJoined  = $participationRepo->findOneBy([
            'event_id'   => $evenement->getId(),
            'user_email' => $userEmail,
        ]);

        return $this->render('employee/events/show.html.twig', [
            'evenement'    => $evenement,
            'alreadyJoined'=> $alreadyJoined !== null,
        ]);
    }
}