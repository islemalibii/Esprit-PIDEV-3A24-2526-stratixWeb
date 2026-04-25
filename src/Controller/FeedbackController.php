<?php

namespace App\Controller;

use App\Entity\EventFeedback;
use App\Entity\Evenement;
use App\Form\FeedbackType;
use App\Repository\EventFeedbackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/employee/feedback')]
class FeedbackController extends AbstractController
{
    #[Route('/new/{id}', name: 'feedback_new')]
    public function new(
        Evenement $evenement,
        Request $request,
        EntityManagerInterface $em,
        EventFeedbackRepository $feedbackRepo
    ): Response {
        $userEmail = $this->getUser()?->getUserIdentifier();

        $existing = $feedbackRepo->findOneBy([
            'evenement'  => $evenement,
            'user_email' => $userEmail,
        ]);

        if ($existing) {
            $this->addFlash('warning', 'Vous avez déjà donné votre avis pour cet événement.');
            return $this->redirectToRoute('emp_event_list');
        }

        $feedback = new EventFeedback();
        $feedback->setEvenement($evenement);
        $feedback->setDateFeedback(new \DateTime());
        $feedback->setUserEmail($userEmail); 

        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($feedback);
            $em->flush();

            $this->addFlash('success', 'Merci pour votre avis!');
            return $this->redirectToRoute('emp_event_list');
        }

        return $this->render('employee/events/feedback.html.twig', [
            'form'      => $form->createView(),
            'evenement' => $evenement,
        ]);
    }
}