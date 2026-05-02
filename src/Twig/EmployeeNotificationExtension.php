<?php

namespace App\Twig;

use App\Repository\TacheRepository;
use App\Repository\ProduitRepository;
use App\Repository\ProjetRepository;
use App\Repository\EvenementRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class EmployeeNotificationExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private TacheRepository   $tacheRepo,
        private ProduitRepository $produitRepo,
        private ProjetRepository  $projetRepo,
        private Security          $security,
        private RequestStack      $requestStack
    ) {}

    public function getGlobals(): array
    {
        try {
            $user = $this->security->getUser();
            if (!$user) return ['employee_notifications' => []];

            // Admins ont leurs propres notifs
            if ($this->security->isGranted('ROLE_ADMIN')) {
                return ['employee_notifications' => []];
            }

            $session  = $this->requestStack->getSession();
            $readIds  = $session->get('emp_notif_read_ids', []);

            // Si "tout lu" → retourner vide
            foreach ($readIds as $id) {
                if (str_starts_with($id, 'all_read_')) return ['employee_notifications' => []];
            }
            $notifs   = [];
            $userId   = $user->getId();
            $now      = new \DateTime();

            // 1. Tâches assignées à cet employé (statut != terminé)
            $taches = $this->tacheRepo->findBy(['employe_id' => $userId]);
            foreach ($taches as $t) {
                if ($t->getStatut() === 'terminé') continue;
                $key = 'tache_'.$t->getId();
                if (in_array($key, $readIds)) continue;

                // Tâche en retard
                if ($t->getDeadline() && $t->getDeadline() < $now) {
                    $notifs[] = [
                        'title'   => 'Tâche en retard : ' . $t->getTitre(),
                        'message' => 'Deadline dépassée le ' . $t->getDeadline()->format('d/m/Y'),
                        'date'    => \DateTime::createFromInterface($t->getDeadline()),
                        'color'   => '#dc2626',
                        'icon'    => 'ti-clock-exclamation',
                        'key'     => $key,
                    ];
                }
                // Tâche urgente
                elseif ($t->getPriorite() === 'haute' || $t->getPriorite() === 'urgente') {
                    $notifs[] = [
                        'title'   => 'Tâche urgente : ' . $t->getTitre(),
                        'message' => 'Priorité : ' . strtoupper($t->getPriorite() ?? ''),
                        'date'    => null,
                        'color'   => '#f97316',
                        'icon'    => 'ti-alert-triangle',
                        'key'     => $key,
                    ];
                }
                // Toute tâche en cours
                else {
                    $notifs[] = [
                        'title'   => 'Tâche assignée : ' . $t->getTitre(),
                        'message' => 'Statut : ' . ($t->getStatut() ?? 'en cours'),
                        'date'    => $t->getDeadline() ? \DateTime::createFromInterface($t->getDeadline()) : null,
                        'color'   => '#4f46e5',
                        'icon'    => 'ti-checklist',
                        'key'     => $key,
                    ];
                }
            }

            // 2. Stock faible (pour tous les responsables et ceo)
            if (in_array($user->getRole(), ['responsable_production', 'responsable_rh', 'responsable_projet', 'ceo'])) {
                $produits = $this->produitRepo->findAll();
                foreach ($produits as $p) {
                    if ($p->getStockActuel() !== null && $p->getStockMin() !== null
                        && $p->getStockActuel() <= $p->getStockMin()) {
                        $key = 'stock_'.$p->getId();
                        if (in_array($key, $readIds)) continue;
                        $notifs[] = [
                            'title'   => 'Stock faible : ' . $p->getNom(),
                            'message' => 'Stock actuel : ' . $p->getStockActuel() . ' (min: ' . $p->getStockMin() . ')',
                            'date'    => null,
                            'color'   => '#eab308',
                            'icon'    => 'ti-package',
                            'key'     => $key,
                        ];
                    }
                }
            }

            // 3. Projets dont l'utilisateur est responsable et deadline proche (7 jours)
            $projets = $this->projetRepo->findAll();
            foreach ($projets as $p) {
                if (!$p->getResponsable() || $p->getResponsable()->getId() !== $userId) continue;
                if ($p->isIsArchived()) continue;
                $dateFin = $p->getDateFin();
                if ($dateFin) {
                    $diff = $now->diff($dateFin)->days;
                    if ($dateFin > $now && $diff <= 7) {
                        $key = 'projet_'.$p->getId();
                        if (in_array($key, $readIds)) continue;
                        $notifs[] = [
                            'title'   => 'Projet bientôt terminé : ' . $p->getNom(),
                            'message' => 'Deadline dans ' . $diff . ' jour(s)',
                            'date'    => \DateTime::createFromInterface($dateFin),
                            'color'   => '#4f46e5',
                            'icon'    => 'ti-folder-exclamation',
                            'key'     => $key,
                        ];
                    }
                }
            }

            // Trier par date et limiter à 5
            usort($notifs, function($a, $b) {
                if (!$a['date'] && !$b['date']) return 0;
                if (!$a['date']) return 1;
                if (!$b['date']) return -1;
                return $b['date'] <=> $a['date'];
            });

            return ['employee_notifications' => array_slice($notifs, 0, 5)];

        } catch (\Exception $e) {
            return ['employee_notifications' => []];
        }
    }
}
