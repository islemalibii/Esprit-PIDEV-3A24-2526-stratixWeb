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

        if (!$recurrence || $recurrence === 'none') return;

        for ($i = 1; $i <= 4; $i++) {
            $newDate = clone $original->getDateEvent();

            if ($recurrence === 'weekly') {
                $newDate->modify("+{$i} week");
            } elseif ($recurrence === 'monthly') {
                $newMonth = (int) $original->getDateEvent()->format('m') + $i;
                $newYear  = (int) $original->getDateEvent()->format('Y');
    
                // Handle year overflow (month > 12)
                while ($newMonth > 12) {
                    $newMonth -= 12;
                    $newYear++;
                }
    
                // Get the last day of the target month
                $lastDayOfMonth = (int) (new \DateTime("{$newYear}-{$newMonth}-01"))
                    ->modify('last day of this month')
                    ->format('d');
    
                // Use original day OR last day of month if original day doesn't exist
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
            $newEvent->setDateEvent($newDate);
            $newEvent->setImageUrl($original->getImageUrl());
            $newEvent->setLatitude($original->getLatitude());
            $newEvent->setLongitude($original->getLongitude());
            $newEvent->setRecurrence('none');

            $this->em->persist($newEvent);
        }

        $this->em->flush();
    }
}