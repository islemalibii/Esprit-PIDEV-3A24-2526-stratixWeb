<?php

namespace App\Service;

use App\Entity\Evenement;
use Doctrine\ORM\EntityManagerInterface;

class RecurrenceService
{
    public function __construct(private EntityManagerInterface $em) {}

    
    public function generateRecurringEvents(Evenement $original): void
    {
        $recurrence = $original->getRecurrence();
        $originalDate = $original->getDateEvent();

        if (!$recurrence || $recurrence === 'none' || !$originalDate instanceof \DateTimeInterface) {
            return;
        }

        $originalDay = (int) $originalDate->format('d');

        for ($i = 1; $i <= 4; $i++) {
            $newDate = clone $originalDate;

            if ($recurrence === 'weekly' && $newDate instanceof \DateTime) {
                $newDate->modify("+{$i} week");
            } elseif ($recurrence === 'monthly') {
                $newMonth = (int) $originalDate->format('m') + $i;
                $newYear  = (int) $originalDate->format('Y');    
                while ($newMonth > 12) {
                    $newMonth -= 12;
                    $newYear++;
                }
    
                $lastDayOfMonth = (int) (new \DateTime("{$newYear}-{$newMonth}-01"))
                    ->modify('last day of this month')
                    ->format('d');
    
                $safeDay = min($originalDay, $lastDayOfMonth);
    
                $newDate = new \DateTime(
                    sprintf('%04d-%02d-%02d', $newYear, $newMonth, $safeDay)
                );
            }

            $newEvent = new Evenement();
            $newEvent->setTitre($original->getTitre() . ' (n° ' . ($i + 1) . ')');
            $newEvent->setDescription($original->getDescription());
            $newEvent->setLieu($original->getLieu());
            $newEvent->setTypeEvent($original->getTypeEvent());
            $newEvent->setStatut($original->getStatut());
            $newEvent->setIsArchived(false);

            if ($newDate instanceof \DateTime) {
                $newEvent->setDateEvent($newDate);
            }

            $newEvent->setImageUrl($original->getImageUrl());
            $newEvent->setLatitude($original->getLatitude());
            $newEvent->setLongitude($original->getLongitude());
            $newEvent->setRecurrence('none');

            $this->em->persist($newEvent);
        }

        $this->em->flush();
    }
}