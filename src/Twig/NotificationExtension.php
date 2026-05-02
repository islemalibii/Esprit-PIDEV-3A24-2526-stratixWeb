<?php

namespace App\Twig;

use App\Repository\UtilisateurRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class NotificationExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private UtilisateurRepository $repo,
        private RequestStack $requestStack,
        private Security $security
    ) {}

    public function getGlobals(): array
    {
        try {
            // Charger uniquement pour les admins
            $user = $this->security->getUser();
            if (!$user || !$this->security->isGranted('ROLE_ADMIN')) {
                return ['admin_notifications' => []];
            }

            $session = $this->requestStack->getSession();
            $readIds = $session->get('notif_read_ids', []);
            $notifications = [];

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

            // Modifications récentes
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

            // Trier par date et limiter à 5
            usort($notifications, function($a, $b) {
                if (!$a['date'] && !$b['date']) return 0;
                if (!$a['date']) return 1;
                if (!$b['date']) return -1;
                return $b['date'] <=> $a['date'];
            });

            return ['admin_notifications' => array_slice($notifications, 0, 5)];

        } catch (\Exception $e) {
            return ['admin_notifications' => []];
        }
    }
}
