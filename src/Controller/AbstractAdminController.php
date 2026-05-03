<?php

namespace App\Controller;

use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

abstract class AbstractAdminController extends AbstractController
{
    /**
     * @return array<int, array{title: string, message: string|null, date: \DateTimeInterface|null, color: string, icon: string}>
     */
    protected function getNotifications(UtilisateurRepository $repo): array
    {
        $notifications = [];

        $newUsers = $repo->findBy([], ['id' => 'DESC'], 3);
        foreach ($newUsers as $u) {
            $notifications[] = [
                'title'   => 'Nouvel utilisateur : ' . $u->getPrenom() . ' ' . $u->getNom(),
                'message' => $u->getEmail(),
                'date'    => $u->getDateAjout(),
                'color'   => '#4f46e5',
                'icon'    => 'ti-user-plus',
            ];
        }

        $locked = $repo->findBy(['account_locked' => true], ['id' => 'DESC'], 3);
        foreach ($locked as $u) {
            $notifications[] = [
                'title'   => 'Compte verrouillé',
                'message' => $u->getPrenom() . ' ' . $u->getNom() . ' (' . ($u->getFailedLoginAttempts() ?? 0) . ' tentatives)',
                'date'    => $u->getLockedUntil(),
                'color'   => '#dc2626',
                'icon'    => 'ti-lock',
            ];
        }

        return $notifications;
    }
}