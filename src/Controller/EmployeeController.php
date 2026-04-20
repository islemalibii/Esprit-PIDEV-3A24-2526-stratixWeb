<?php
// src/Controller/EmployeeController.php

namespace App\Controller;

use App\Repository\TacheRepository;
use App\Repository\PlanningRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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
            $taches = $tacheRepository->findBy(['employeId' => $employe->getId()]);
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
        
        foreach ($taches as $tache) {
            if ($tache->getStatut() === 'A_FAIRE') $aFaire++;
            if ($tache->getStatut() === 'EN_COURS') $enCours++;
            if ($tache->getStatut() === 'TERMINEE') $terminees++;
            if ($tache->getPriorite() === 'HAUTE') $haute++;
            if ($tache->getPriorite() === 'MOYENNE') $moyenne++;
            if ($tache->getPriorite() === 'BASSE') $basse++;
        }
        
        $tachesRecentes = array_slice(array_reverse($taches), 0, 5);
        
        $today = new \DateTime();
        $planningsAVenir = [];
        foreach ($plannings as $planning) {
            // Vérifie les noms corrects des méthodes
            $datePlanning = method_exists($planning, 'getDate') ? $planning->getDate() : null;
            if ($datePlanning && $datePlanning >= $today) {
                $planningsAVenir[] = $planning;
            }
        }
        $planningsAVenir = array_slice($planningsAVenir, 0, 5);
        
        return $this->render('employee/dashboard.html.twig', [
            'total' => count($taches),
            'aFaire' => $aFaire,
            'enCours' => $enCours,
            'terminees' => $terminees,
            'haute' => $haute,
            'moyenne' => $moyenne,
            'basse' => $basse,
            'tachesRecentes' => $tachesRecentes,
            'planningsAVenir' => $planningsAVenir,
            'employe' => $employe,
            'hasTaches' => count($taches) > 0,
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
            $taches = $tacheRepository->findBy(['employeId' => $employe->getId()]);
        } else {
            $taches = [];
        }
        
        return $this->render('employee/taches.html.twig', [
            'taches' => $taches,
            'hasTaches' => count($taches) > 0,
        ]);
    }
    
    #[Route('/employee/plannings', name: 'app_employee_plannings')]
    public function mesPlannings(PlanningRepository $planningRepository): Response
    {
        $plannings = $planningRepository->findAll();
        
        return $this->render('employee/plannings.html.twig', [
            'plannings' => $plannings,
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
            $taches = $tacheRepository->findBy(['employeId' => $employe->getId()]);
        } else {
            $taches = [];
        }
        
        $plannings = $planningRepository->findAll();
        
        $events = [];
        
        foreach ($taches as $tache) {
            if ($tache->getDeadline()) {
                $color = '#ef4444';
                if ($tache->getPriorite() === 'HAUTE') $color = '#dc2626';
                if ($tache->getPriorite() === 'MOYENNE') $color = '#f59e0b';
                if ($tache->getPriorite() === 'BASSE') $color = '#10b981';
                
                $events[] = [
                    'title' => '📌 ' . $tache->getTitre(),
                    'start' => $tache->getDeadline()->format('Y-m-d'),
                    'color' => $color,
                ];
            }
        }
        
        foreach ($plannings as $planning) {
            // Utilise les bonnes méthodes selon ton entité Planning
            $datePlanning = null;
            $titrePlanning = null;
            
            if (method_exists($planning, 'getDate')) {
                $datePlanning = $planning->getDate();
            } elseif (method_exists($planning, 'getDateDebut')) {
                $datePlanning = $planning->getDateDebut();
            }
            
            if (method_exists($planning, 'getTitre')) {
                $titrePlanning = $planning->getTitre();
            } elseif (method_exists($planning, 'getNom')) {
                $titrePlanning = $planning->getNom();
            } elseif (method_exists($planning, 'getLibelle')) {
                $titrePlanning = $planning->getLibelle();
            }
            
            if ($datePlanning && $titrePlanning) {
                $events[] = [
                    'title' => '📅 ' . $titrePlanning,
                    'start' => $datePlanning->format('Y-m-d'),
                    'color' => '#3b82f6',
                ];
            }
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
            $taches = $tacheRepository->findBy(['employeId' => $employe->getId()]);
        } else {
            $taches = [];
        }
        
        $aFaire = [];
        $enCours = [];
        $terminees = [];
        
        foreach ($taches as $tache) {
            if ($tache->getStatut() === 'A_FAIRE') {
                $aFaire[] = $tache;
            } elseif ($tache->getStatut() === 'EN_COURS') {
                $enCours[] = $tache;
            } elseif ($tache->getStatut() === 'TERMINEE') {
                $terminees[] = $tache;
            }
        }
        
        return $this->render('employee/whiteboard.html.twig', [
            'aFaire' => $aFaire,
            'enCours' => $enCours,
            'terminees' => $terminees,
            'hasTaches' => count($taches) > 0,
        ]);
    }
}