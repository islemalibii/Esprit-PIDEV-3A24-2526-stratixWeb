<?php
// src/Controller/EmployeeController.php

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
        PlanningRepository $planningRepository,
        UtilisateurRepository $utilisateurRepository
    ): Response {

        $allUsers = $utilisateurRepository->findAll();
        $employe = !empty($allUsers) ? $allUsers[0] : null;
        
        if ($employe) {
            $taches = $tacheRepository->findBy(['employe_id' => $employe->getId()]);
        } else {
            $taches = [];
        }
        
        $plannings = $planningRepository->findAll();
        
        $aFaire = 0;
        $enCours = 0;
        $terminees = 0;
        $haute = 0;
        $moyenne = 0;
        $basse = 0;
        

        /** @var Utilisateur $employe */
        $employe   = $this->getUser();
        $taches    = $employe ? $tacheRepository->findBy(['employeId' => $employe->getId()]) : [];
        $plannings = $employe ? $planningRepository->findBy(['employeId' => $employe->getId()]) : [];

        $aFaire = 0; $enCours = 0; $terminees = 0;
        $haute  = 0; $moyenne = 0; $basse     = 0;

        foreach ($taches as $tache) {
            if ($tache->getStatut() === 'A_FAIRE')   $aFaire++;
            if ($tache->getStatut() === 'EN_COURS')  $enCours++;
            if ($tache->getStatut() === 'TERMINEE')  $terminees++;
            if ($tache->getPriorite() === 'HAUTE')   $haute++;
            if ($tache->getPriorite() === 'MOYENNE') $moyenne++;
            if ($tache->getPriorite() === 'BASSE')   $basse++;
        }

        $tachesRecentes = array_slice(array_reverse($taches), 0, 5);

        $today = new \DateTime();
        $planningsAVenir = [];
        foreach ($plannings as $planning) {
            $datePlanning = $planning->getDate();
            if ($datePlanning && $datePlanning >= $today) {
                $planningsAVenir[] = $planning;
            }
        }
        $planningsAVenir = array_slice($planningsAVenir, 0, 5);

        return $this->render('employee/dashboard.html.twig', [
            'total'           => count($taches),
            'aFaire'          => $aFaire,
            'enCours'         => $enCours,
            'terminees'       => $terminees,
            'haute'           => $haute,
            'moyenne'         => $moyenne,
            'basse'           => $basse,
            'tachesRecentes'  => $tachesRecentes,
            'planningsAVenir' => $planningsAVenir,
            'employe'         => $employe,
            'hasTaches'       => count($taches) > 0,
        ]);
    }

    #[Route('/employee/taches', name: 'app_employee_taches')]
    public function mesTaches(
        TacheRepository $tacheRepository,
        UtilisateurRepository $utilisateurRepository
    ): Response {
        $allUsers = $utilisateurRepository->findAll();
        $employe = !empty($allUsers) ? $allUsers[0] : null;
        
        if ($employe) {
            $taches = $tacheRepository->findBy(['employe_id' => $employe->getId()]);
        } else {
            $taches = [];
        }
        
        return $this->render('employee/taches.html.twig', [
            'taches'    => $taches,
            'hasTaches' => count($taches) > 0,
        ]);
    }

    #[Route('/employee/plannings', name: 'app_employee_plannings')]
    public function mesPlannings(PlanningRepository $planningRepository): Response
    {
        /** @var Utilisateur $employe */
        $employe   = $this->getUser();
        $plannings = $employe ? $planningRepository->findBy(['employeId' => $employe->getId()]) : [];

        return $this->render('employee/plannings.html.twig', [
            'plannings'    => $plannings,
            'hasPlannings' => count($plannings) > 0,
        ]);
    }

    #[Route('/employee/calendar', name: 'app_employee_calendar')]
    public function calendar(
        TacheRepository $tacheRepository,
        PlanningRepository $planningRepository,
        UtilisateurRepository $utilisateurRepository
    ): Response {
        $allUsers = $utilisateurRepository->findAll();
        $employe = !empty($allUsers) ? $allUsers[0] : null;
        
        if ($employe) {
            $taches = $tacheRepository->findBy(['employe_id' => $employe->getId()]);
        } else {
            $taches = [];
        }
        
        $plannings = $planningRepository->findAll();
        
        $events = [];
        
        foreach ($taches as $tache) {
            if ($tache->getDeadline()) {
                $color = '#ef4444';
                if ($tache->getPriorite() === 'HAUTE')   $color = '#dc2626';
                if ($tache->getPriorite() === 'MOYENNE') $color = '#f59e0b';
                if ($tache->getPriorite() === 'BASSE')   $color = '#10b981';

                $events[] = [
                    'id'       => 'tache_' . $tache->getId(),
                    'title'    => '📌 ' . $tache->getTitre(),
                    'start'    => $tache->getDeadline()->format('Y-m-d'),
                    'color'    => $color,
                    'priorite' => $tache->getPriorite(),
                    'statut'   => $tache->getStatut(),
                    'type'     => 'tache',
                ];
            }
        }

        foreach ($plannings as $planning) {
            $heureDebut = $planning->getHeureDebut() ? $planning->getHeureDebut()->format('H:i') : '';
            $heureFin   = $planning->getHeureFin()   ? $planning->getHeureFin()->format('H:i')   : '';
            $shift      = $planning->getTypeShift()  ?? 'Planning';
            $titre      = $shift;
            if ($heureDebut && $heureFin) $titre .= ' (' . $heureDebut . ' - ' . $heureFin . ')';

            $events[] = [
                'id'       => 'planning_' . $planning->getId(),
                'title'    => '📅 ' . $titre,
                'start'    => $planning->getDate()->format('Y-m-d'),
                'color'    => '#3b82f6',
                'priorite' => null,
                'statut'   => null,
                'type'     => 'planning',
            ];
        }

        return $this->render('employee/calendar.html.twig', [
            'events' => json_encode($events),
        ]);
    }

    #[Route('/employee/whiteboard', name: 'app_employee_whiteboard')]
    public function whiteboard(
        TacheRepository $tacheRepository,
        UtilisateurRepository $utilisateurRepository
    ): Response {
        $allUsers = $utilisateurRepository->findAll();
        $employe = !empty($allUsers) ? $allUsers[0] : null;
        
        if ($employe) {
            $taches = $tacheRepository->findBy(['employe_id' => $employe->getId()]);
        } else {
            $taches = [];
        }
        
        $aFaire = [];
        $enCours = [];
        $terminees = [];

        foreach ($taches as $tache) {
            if ($tache->getStatut() === 'A_FAIRE')      $aFaire[]    = $tache;
            elseif ($tache->getStatut() === 'EN_COURS') $enCours[]   = $tache;
            elseif ($tache->getStatut() === 'TERMINEE') $terminees[] = $tache;
        }

        return $this->render('employee/whiteboard.html.twig', [
            'aFaire'    => $aFaire,
            'enCours'   => $enCours,
            'terminees' => $terminees,
            'hasTaches' => count($taches) > 0,
        ]);
    }

    #[Route('/employee/tache/{id}/move', name: 'app_employee_tache_move', methods: ['POST'])]
    public function moveTache(
        int $id,
        Request $request,
        TacheRepository $tacheRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var Utilisateur $user */
        $user  = $this->getUser();
        $tache = $tacheRepository->find($id);

        if (!$tache || $tache->getEmployeId() !== $user->getId()) {
            return $this->json(['success' => false, 'error' => 'Non autorisé'], 403);
        }

        $body      = json_decode($request->getContent(), true);
        $newStatus = $body['status'] ?? $request->request->get('status');

        $statusMap = [
            'a_faire'   => 'A_FAIRE',
            'en_cours'  => 'EN_COURS',
            'terminees' => 'TERMINEE',
            'A_FAIRE'   => 'A_FAIRE',
            'EN_COURS'  => 'EN_COURS',
            'TERMINEE'  => 'TERMINEE',
        ];

        $mapped = $statusMap[$newStatus] ?? null;

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
        /** @var Utilisateur $user */
        $user   = $this->getUser();
        $errors = [];

        if ($request->isMethod('POST')) {
            $nom       = trim($request->request->get('nom', ''));
            $prenom    = trim($request->request->get('prenom', ''));
            $tel       = trim($request->request->get('tel', ''));
            $currentPw = $request->request->get('current_password', '');
            $newPw     = $request->request->get('new_password', '');
            $confirmPw = $request->request->get('confirm_password', '');

            if (!$nom)    $errors['nom']    = 'Le nom est obligatoire.';
            if (!$prenom) $errors['prenom'] = 'Le prénom est obligatoire.';
            if ($tel && !preg_match('/^\d{8}$/', $tel)) {
                $errors['tel'] = 'Le téléphone doit contenir 8 chiffres.';
            }

            if ($newPw) {
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
                $user->setNom($nom)->setPrenom($prenom)->setTel($tel ?: null);
                if ($newPw) {
                    $user->setPassword($hasher->hashPassword($user, $newPw));
                }
                $em->flush();
                $this->addFlash('success', 'Profil mis à jour.');
                return $this->redirectToRoute('employee_profile');
            }
        }

        return $this->render('employee/profile_edit.html.twig', [
            'user'   => $user,
            'errors' => $errors,
        ]);
    }
}