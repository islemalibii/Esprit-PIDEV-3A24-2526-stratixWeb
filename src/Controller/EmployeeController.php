<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\TacheRepository;
use App\Repository\PlanningRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class EmployeeController extends AbstractController
{
    #[Route('/employee/dashboard', name: 'app_employee_dashboard')]
    public function dashboard(
        TacheRepository $tacheRepository,
        PlanningRepository $planningRepository
    ): Response {
        $employe = $this->getUser();
        if (!$employe instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $taches = $tacheRepository->findBy(['employeId' => $employe->getId()]);
        $plannings = $planningRepository->findBy(['employeId' => $employe->getId()]);

        $aFaire = $enCours = $terminees = $haute = $moyenne = $basse = 0;
        foreach ($taches as $tache) {
            match ($tache->getStatut()) {
                'A_FAIRE' => $aFaire++,
                'EN_COURS' => $enCours++,
                'TERMINEE' => $terminees++,
                default => null,
            };
            match ($tache->getPriorite()) {
                'HAUTE' => $haute++,
                'MOYENNE' => $moyenne++,
                'BASSE' => $basse++,
                default => null,
            };
        }

        $tachesRecentes = array_slice(array_reverse($taches), 0, 5);
        $today = new \DateTime();
        $planningsAVenir = [];
        foreach ($plannings as $p) {
            $date = $p->getDate();
            if ($date && $date >= $today) {
                $planningsAVenir[] = $p;
            }
        }
        $planningsAVenir = array_slice($planningsAVenir, 0, 5);

        return $this->render('employee/dashboard.html.twig', [
            'total' => count($taches),
            'aFaire', 'enCours', 'terminees',
            'haute', 'moyenne', 'basse',
            'tachesRecentes',
            'planningsAVenir',
            'employe' => $employe,
            'hasTaches' => !empty($taches),
        ]);
    }

    #[Route('/employee/taches', name: 'app_employee_taches')]
    public function mesTaches(TacheRepository $tacheRepository): Response
    {
        $employe = $this->getUser();
        if (!$employe instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }
        $taches = $tacheRepository->findBy(['employeId' => $employe->getId()]);

        return $this->render('employee/taches.html.twig', [
            'taches' => $taches,
            'hasTaches' => !empty($taches),
        ]);
    }

    #[Route('/employee/plannings', name: 'app_employee_plannings')]
    public function mesPlannings(PlanningRepository $planningRepository): Response
    {
        $employe = $this->getUser();
        if (!$employe instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }
        $plannings = $planningRepository->findBy(['employeId' => $employe->getId()]);

        return $this->render('employee/plannings.html.twig', [
            'plannings' => $plannings,
            'hasPlannings' => !empty($plannings),
        ]);
    }

    #[Route('/employee/calendar', name: 'app_employee_calendar')]
    public function calendar(
        TacheRepository $tacheRepository,
        PlanningRepository $planningRepository
    ): Response {
        $employe = $this->getUser();
        if (!$employe instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $taches = $tacheRepository->findBy(['employeId' => $employe->getId()]);
        $plannings = $planningRepository->findBy(['employeId' => $employe->getId()]);

        $events = [];

        foreach ($taches as $tache) {
            $deadline = $tache->getDeadline();
            if (!$deadline) continue;

            $color = match ($tache->getPriorite()) {
                'HAUTE' => '#dc2626',
                'MOYENNE' => '#f59e0b',
                default => '#10b981',
            };
            $events[] = [
                'id' => 'tache_' . $tache->getId(),
                'title' => '📌 ' . ($tache->getTitre() ?? ''),
                'start' => $deadline->format('Y-m-d'),
                'color' => $color,
                'priorite' => $tache->getPriorite(),
                'statut' => $tache->getStatut(),
                'type' => 'tache',
            ];
        }

        foreach ($plannings as $planning) {
            $date = $planning->getDate();
            if (!$date) continue;

            $heureDebut = $planning->getHeureDebut()?->format('H:i') ?: '';
            $heureFin   = $planning->getHeureFin()?->format('H:i') ?: '';
            $shift = $planning->getTypeShift() ?? 'Planning';
            $titre = $shift;
            if ($heureDebut && $heureFin) {
                $titre .= " ($heureDebut - $heureFin)";
            }
            $events[] = [
                'id' => 'planning_' . $planning->getId(),
                'title' => '📅 ' . $titre,
                'start' => $date->format('Y-m-d'),
                'color' => '#3b82f6',
                'priorite' => null,
                'statut' => null,
                'type' => 'planning',
            ];
        }

        return $this->render('employee/calendar.html.twig', [
            'events' => json_encode($events),
        ]);
    }

    #[Route('/employee/whiteboard', name: 'app_employee_whiteboard')]
    public function whiteboard(TacheRepository $tacheRepository): Response
    {
        $employe = $this->getUser();
        if (!$employe instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }
        $taches = $tacheRepository->findBy(['employeId' => $employe->getId()]);

        $aFaire = $enCours = $terminees = [];
        foreach ($taches as $tache) {
            match ($tache->getStatut()) {
                'A_FAIRE' => $aFaire[] = $tache,
                'EN_COURS' => $enCours[] = $tache,
                'TERMINEE' => $terminees[] = $tache,
                default => null,
            };
        }

        return $this->render('employee/whiteboard.html.twig', [
            'aFaire', 'enCours', 'terminees',
            'hasTaches' => !empty($taches),
        ]);
    }

    #[Route('/employee/tache/{id}/move', name: 'app_employee_tache_move', methods: ['POST'])]
    public function moveTache(
        int $id,
        Request $request,
        TacheRepository $tacheRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            return $this->json(['success' => false, 'error' => 'Non autorisé'], 403);
        }

        $tache = $tacheRepository->find($id);
        if (!$tache || $tache->getEmployeId() !== $user->getId()) {
            return $this->json(['success' => false, 'error' => 'Tâche non trouvée ou non autorisée'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $newStatus = null;
        if (is_array($data) && isset($data['status']) && is_string($data['status'])) {
            $newStatus = $data['status'];
        }
        if ($newStatus === null) {
            $raw = $request->request->get('status');
            $newStatus = is_string($raw) ? $raw : null;
        }

        $statusMap = [
            'a_faire' => 'A_FAIRE',
            'en_cours' => 'EN_COURS',
            'terminees' => 'TERMINEE',
            'A_FAIRE' => 'A_FAIRE',
            'EN_COURS' => 'EN_COURS',
            'TERMINEE' => 'TERMINEE',
        ];

        $mapped = null;
        if ($newStatus !== null && isset($statusMap[$newStatus])) {
            $mapped = $statusMap[$newStatus];
        }

        if (!$mapped) {
            return $this->json(['success' => false, 'error' => 'Statut invalide'], 400);
        }

        $tache->setStatut($mapped);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/employee/profile', name: 'employee_profile')]
    public function profile(): Response
    {
        return $this->render('employee/profile.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/employee/profile/edit', name: 'employee_profile_edit', methods: ['GET', 'POST'])]
    public function profileEdit(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException();
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $nom = trim((string) ($request->request->get('nom') ?? ''));
            $prenom = trim((string) ($request->request->get('prenom') ?? ''));
            $tel = trim((string) ($request->request->get('tel') ?? ''));
            $currentPw = (string) ($request->request->get('current_password') ?? '');
            $newPw = (string) ($request->request->get('new_password') ?? '');
            $confirmPw = (string) ($request->request->get('confirm_password') ?? '');

            if ($nom === '') $errors['nom'] = 'Le nom est obligatoire.';
            if ($prenom === '') $errors['prenom'] = 'Le prénom est obligatoire.';
            if ($tel !== '' && !preg_match('/^\d{8}$/', $tel)) {
                $errors['tel'] = 'Le téléphone doit contenir 8 chiffres.';
            }

            if ($newPw !== '') {
                if (!$hasher->isPasswordValid($user, $currentPw)) {
                    $errors['current_password'] = 'Mot de passe actuel incorrect.';
                } elseif (strlen($newPw) < 8) {
                    $errors['new_password'] = 'Minimum 8 caractères.';
                } elseif (!preg_match('/[A-Z]/', $newPw)) {
                    $errors['new_password'] = 'Au moins une majuscule requise.';
                } elseif (!preg_match('/[0-9]/', $newPw)) {
                    $errors['new_password'] = 'Au moins un chiffre requis.';
                } elseif ($newPw !== $confirmPw) {
                    $errors['confirm_password'] = 'Les mots de passe ne correspondent pas.';
                }
            }

            if (empty($errors)) {
                $user->setNom($nom)->setPrenom($prenom)->setTel($tel !== '' ? $tel : null);
                if ($newPw !== '') {
                    $user->setPassword($hasher->hashPassword($user, $newPw));
                }
                $em->flush();
                $this->addFlash('success', 'Profil mis à jour.');
                return $this->redirectToRoute('employee_profile');
            }
        }

        return $this->render('employee/profile_edit.html.twig', [
            'user' => $user,
            'errors' => $errors,
        ]);
    }
}