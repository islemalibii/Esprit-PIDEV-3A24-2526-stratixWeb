<?php

namespace App\Twig;

use App\Repository\UtilisateurRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class NotificationExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private UtilisateurRepository $repo,
        private RequestStack $requestStack
    ) {}

    public function getGlobals(): array
    {
        $notifications = [];

        try {
            $session = $this->requestStack->getSession();
            $readIds = $session->get('notif_read_ids', []);

            // Nouveaux utilisateurs
            $newUsers = $this->repo->findBy([], ['id' => 'DESC'], 5);
            foreach ($newUsers as $u) {
                if (in_array('new_'.$u->getId(), $readIds)) continue;
                $notifications[] = [
                    'title'   => 'Nouvel utilisateur : ' . $u->getPrenom() . ' ' . $u->getNom(),
                    'message' => $u->getEmail(),
                    'date'    => $u->getDateAjout(),
                    'color'   => '#4f46e5',
                    'icon'    => 'ti-user-plus',
                ];
            }

            // Modifications récentes (updated_at non null)
            $updated = $this->repo->createQueryBuilder('u')
                ->where('u.updated_at IS NOT NULL')
                ->orderBy('u.updated_at', 'DESC')
                ->setMaxResults(5)
                ->getQuery()->getResult();
            foreach ($updated as $u) {
                if (in_array('edit_'.$u->getId(), $readIds)) continue;
                $notifications[] = [
                    'title'   => 'Profil modifié : ' . $u->getPrenom() . ' ' . $u->getNom(),
                    'message' => 'Modifié le ' . $u->getUpdatedAt()->format('d/m/Y à H:i'),
                    'date'    => $u->getUpdatedAt(),
                    'color'   => '#f97316',
                    'icon'    => 'ti-edit',
                ];
            }

            // Comptes verrouillés
            $locked = $this->repo->findBy(['account_locked' => true], ['id' => 'DESC'], 5);
            foreach ($locked as $u) {
                if (in_array('lock_'.$u->getId(), $readIds)) continue;
                $notifications[] = [
                    'title'   => 'Compte verrouillé',
                    'message' => $u->getPrenom() . ' ' . $u->getNom() . ' (' . ($u->getFailedLoginAttempts() ?? 0) . ' tentatives)',
                    'date'    => $u->getLockedAt() ?? $u->getLockedUntil(),
                    'color'   => '#dc2626',
                    'icon'    => 'ti-lock',
                ];
            }

            // Trier par date décroissante et limiter à 5
            usort($notifications, function (array $a, array $b): int {
                $dateA = $a['date'] instanceof \DateTimeInterface ? $a['date'] : null;
                $dateB = $b['date'] instanceof \DateTimeInterface ? $b['date'] : null;

                if ($dateA === null && $dateB === null) return 0;
                if ($dateA === null) return 1;
                if ($dateB === null) return -1;

                return $dateB <=> $dateA;
            });
            $notifications = array_slice($notifications, 0, 5);

        } catch (\Exception $e) {}

        return ['admin_notifications' => $notifications];
    }
}