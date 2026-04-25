<?php

namespace App\Service;

use App\Entity\Participation;
use App\Entity\Evenement;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

class ParticipationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private TransportInterface      $eventMailer,
        private ParticipationRepository $participationRepository
    ) {}

    public function participate(Evenement $evenement, string $userEmail): array
    {
        $existing = $this->participationRepository->findOneBy([
            'event_id'   => $evenement->getId(),
            'user_email' => $userEmail,
        ]);

        if ($existing) {
            return ['success' => false, 'message' => 'Vous participez déjà à cet événement.'];
        }

        $participation = new Participation();
        $participation->setEventId($evenement->getId());
        $participation->setUserEmail($userEmail);
        $participation->setParticipationDate(new \DateTime());

        $this->em->persist($participation);
        $this->em->flush();

        $email = (new Email())
            ->from('islem.alibii@gmail.com')
            ->to($userEmail)
            ->subject('Confirmation de participation — ' . $evenement->getTitre())
            ->html("
                <h2>Bonjour,</h2>
                <p>Votre participation à l'événement <strong>{$evenement->getTitre()}</strong> a été confirmée.</p>
                <p><strong>Date:</strong> {$evenement->getDateEvent()->format('d/m/Y')}</p>
                <p><strong>Lieu:</strong> {$evenement->getLieu()}</p>
                <br>
                <p>À bientôt!</p>
            ");

            $email->getHeaders()->addTextHeader('X-Transport', 'event');

            $this->eventMailer->send($email);

        return ['success' => true, 'message' => 'Participation confirmée! Un email vous a été envoyé.'];
    }

    public function cancelParticipation(Evenement $evenement, string $userEmail): array
    {
        $participation = $this->participationRepository->findOneBy([
            'event_id'   => $evenement->getId(),
            'user_email' => $userEmail,
        ]);

        if (!$participation) {
            return ['success' => false, 'message' => 'Vous ne participez pas à cet événement.'];
        }

        $this->em->remove($participation);
        $this->em->flush();

        $email = (new Email())
            ->from('islem.alibii@gmail.com')
            ->to($userEmail)
            ->subject('Annulation de participation — ' . $evenement->getTitre())
            ->html("
                <h2>Bonjour,</h2>
                <p>Votre participation à l'événement <strong>{$evenement->getTitre()}</strong> a été annulée.</p>
                <p><strong>Date:</strong> {$evenement->getDateEvent()->format('d/m/Y')}</p>
                <p><strong>Lieu:</strong> {$evenement->getLieu()}</p>
                <br>
                <p>Nous espérons vous voir à un prochain événement!</p>
            ");

            $email->getHeaders()->addTextHeader('X-Transport', 'event');

            $this->eventMailer->send($email);

        return ['success' => true, 'message' => 'Participation annulée. Un email vous a été envoyé.'];
    }
}