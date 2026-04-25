<?php

namespace App\Controller\Api;

use App\Repository\TacheRepository;
use App\Repository\PlanningRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/export')]
class ExportPdfController extends AbstractController
{
    #[Route('/taches', name: 'api_export_taches_pdf', methods: ['GET'])]
    public function exportTaches(TacheRepository $tacheRepository): Response
    {
        $taches = $tacheRepository->findAll();
        
        // Calcul des statistiques
        $total = count($taches);
        $aFaire = 0;
        $enCours = 0;
        $terminees = 0;
        
        foreach ($taches as $t) {
            if ($t->getStatut() === 'A_FAIRE') $aFaire++;
            if ($t->getStatut() === 'EN_COURS') $enCours++;
            if ($t->getStatut() === 'TERMINEE') $terminees++;
        }
        
        // Générer le HTML du PDF
        $html = $this->renderView('pdf/taches.html.twig', [
            'taches' => $taches,
            'total' => $total,
            'aFaire' => $aFaire,
            'enCours' => $enCours,
            'terminees' => $terminees,
            'date_export' => (new \DateTime())->format('d/m/Y H:i:s')
        ]);
        
        // Configurer Dompdf
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="taches_' . date('Y-m-d') . '.pdf"'
        ]);
    }

    #[Route('/tache/{id}', name: 'api_export_tache_pdf', methods: ['GET'])]
    public function exportTache(int $id, TacheRepository $tacheRepository): Response
    {
        $tache = $tacheRepository->find($id);
        
        if (!$tache) {
            throw $this->createNotFoundException('Tâche non trouvée');
        }
        
        $html = $this->renderView('pdf/tache_detail.html.twig', [
            'tache' => $tache,
            'date_export' => (new \DateTime())->format('d/m/Y H:i:s')
        ]);
        
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="tache_' . $id . '.pdf"'
        ]);
    }

    #[Route('/dashboard', name: 'api_export_dashboard_pdf', methods: ['GET'])]
    public function exportDashboard(TacheRepository $tacheRepository, PlanningRepository $planningRepository): Response
    {
        $taches = $tacheRepository->findAll();
        $plannings = $planningRepository->findAll();
        
        $total = count($taches);
        $aFaire = count(array_filter($taches, fn($t) => $t->getStatut() === 'A_FAIRE'));
        $enCours = count(array_filter($taches, fn($t) => $t->getStatut() === 'EN_COURS'));
        $terminees = count(array_filter($taches, fn($t) => $t->getStatut() === 'TERMINEE'));
        
        $html = $this->renderView('pdf/dashboard.html.twig', [
            'total' => $total,
            'aFaire' => $aFaire,
            'enCours' => $enCours,
            'terminees' => $terminees,
            'totalPlannings' => count($plannings),
            'date_export' => (new \DateTime())->format('d/m/Y H:i:s')
        ]);
        
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="dashboard_' . date('Y-m-d') . '.pdf"'
        ]);
    }

    #[Route('/plannings', name: 'api_export_plannings_pdf', methods: ['GET'])]
    public function exportPlannings(PlanningRepository $planningRepository): Response
    {
        $plannings = $planningRepository->findAll();
        
        $html = $this->renderView('pdf/plannings.html.twig', [
            'plannings' => $plannings,
            'total' => count($plannings),
            'date_export' => (new \DateTime())->format('d/m/Y H:i:s')
        ]);
        
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="plannings_' . date('Y-m-d') . '.pdf"'
        ]);
    }
}