<?php

namespace App\Controller\Api;

use App\Repository\PlanningRepository;
use App\Repository\UtilisateurRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/planning/export')]
class ExportExcelController extends AbstractController
{
    public function __construct(
        private PlanningRepository $planningRepository,
        private UtilisateurRepository $utilisateurRepository
    ) {}

    #[Route('/excel', name: 'api_planning_export_excel', methods: ['GET'])]
    public function exportToExcel(Request $request): Response
    {
        $typeShift  = $request->query->get('type_shift');
        $employeId  = $request->query->get('employe_id');
        $download   = $request->query->get('download'); // ?download=1 pour forcer le téléchargement

        $plannings = $this->planningRepository->findAll();

        if ($typeShift) {
            $plannings = array_filter($plannings, fn($p) => $p->getTypeShift() === $typeShift);
        }
        if ($employeId) {
            $plannings = array_filter($plannings, fn($p) => $p->getEmployeId() == $employeId);
        }

        $employes = [];
        foreach ($this->utilisateurRepository->findAll() as $u) {
            $employes[$u->getId()] = $u->getPrenom() . ' ' . $u->getNom();
        }

        // ── MODE TÉLÉCHARGEMENT (?download=1) ──────────────────────────────
        if ($download) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Plannings');

            $sheet->setCellValue('A1', 'Employé');
            $sheet->setCellValue('B1', 'Date');
            $sheet->setCellValue('C1', 'Heure Début');
            $sheet->setCellValue('D1', 'Heure Fin');
            $sheet->setCellValue('E1', 'Type Shift');

            $row = 2;
            foreach ($plannings as $p) {
                $sheet->setCellValue('A' . $row, $employes[$p->getEmployeId()] ?? 'Non assigné');
                $sheet->setCellValue('B' . $row, $p->getDate()->format('d/m/Y'));
                $sheet->setCellValue('C' . $row, $p->getHeureDebut()?->format('H:i') ?? '');
                $sheet->setCellValue('D' . $row, $p->getHeureFin()?->format('H:i') ?? '');
                $sheet->setCellValue('E' . $row, $p->getTypeShift());
                $row++;
            }

            foreach (range('A', 'E') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer   = new Xlsx($spreadsheet);
            $fileName = 'plannings_' . date('Y-m-d') . '.xlsx';
            $tempFile = tempnam(sys_get_temp_dir(), $fileName);
            $writer->save($tempFile);

            return $this->file($tempFile, $fileName, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        }

        // ── MODE AFFICHAGE DANS L'INTERFACE (par défaut) ───────────────────
        $shiftBadges = [
            'JOUR'      => ['label' => '☀️ JOUR',       'class' => 'bg-info'],
            'SOIR'      => ['label' => '🌆 SOIR',       'class' => 'bg-warning'],
            'NUIT'      => ['label' => '🌙 NUIT',       'class' => 'bg-dark'],
            'CONGE'     => ['label' => '🏖️ CONGÉ',     'class' => 'bg-warning text-dark'],
            'MALADIE'   => ['label' => '🤒 MALADIE',    'class' => 'bg-danger'],
            'FORMATION' => ['label' => '📚 FORMATION',  'class' => 'bg-primary'],
        ];

        $titre = $typeShift
            ? 'Plannings — ' . ($shiftBadges[$typeShift]['label'] ?? $typeShift)
            : ($employeId ? 'Plannings de ' . ($employes[$employeId] ?? 'l\'employé') : 'Tous les plannings');

        $downloadUrl = $request->getRequestUri()
            . (str_contains($request->getRequestUri(), '?') ? '&' : '?')
            . 'download=1';

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <title>{$titre}</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            <style>
                body { background: #f8f9fa; padding: 30px; font-family: 'Segoe UI', sans-serif; }
                .table-container { background: white; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); padding: 24px; }
                .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
                .page-title { font-size: 1.4rem; font-weight: 700; color: #2c3e50; margin: 0; }
                thead th { background: #2c3e50 !important; color: white !important; border: none; font-weight: 600; white-space: nowrap; }
                .table { border-radius: 8px; overflow: hidden; }
                .badge { font-size: 0.8rem; padding: 5px 10px; border-radius: 20px; }
                .count-badge { background: #e9ecef; color: #495057; border-radius: 20px; padding: 4px 12px; font-size: 0.85rem; font-weight: 600; }
                @media print { .no-print { display: none !important; } body { padding: 10px; } }
            </style>
        </head>
        <body>
            <div class="table-container">
                <div class="page-header">
                    <div>
                        <h1 class="page-title"><i class="fas fa-calendar-alt me-2 text-primary"></i>{$titre}</h1>
                        <span class="count-badge mt-1 d-inline-block">
                            <i class="fas fa-list me-1"></i> {$this->countPlannings($plannings)} entrée(s)
                        </span>
                    </div>
                    <div class="d-flex gap-2 no-print">
                        <a href="{$downloadUrl}" class="btn btn-success btn-sm">
                            <i class="fas fa-download me-1"></i> Télécharger .xlsx
                        </a>
                        <button onclick="window.print()" class="btn btn-secondary btn-sm">
                            <i class="fas fa-print me-1"></i> Imprimer
                        </button>
                        <button onclick="window.history.back()" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Retour
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th><i class="fas fa-user me-1"></i> Employé</th>
                                <th><i class="fas fa-calendar me-1"></i> Date</th>
                                <th><i class="fas fa-clock me-1"></i> Début</th>
                                <th><i class="fas fa-clock me-1"></i> Fin</th>
                                <th><i class="fas fa-tag me-1"></i> Type</th>
                            </tr>
                        </thead>
                        <tbody>
        HTML;

        $rows = '';
        foreach ($plannings as $p) {
            $shift     = $p->getTypeShift();
            $badge     = $shiftBadges[$shift] ?? ['label' => $shift, 'class' => 'bg-secondary'];
            $employe   = htmlspecialchars($employes[$p->getEmployeId()] ?? 'Non assigné');
            $date      = $p->getDate() ? $p->getDate()->format('d/m/Y') : '';
            $debut     = $p->getHeureDebut() ? $p->getHeureDebut()->format('H:i') : '—';
            $fin       = $p->getHeureFin() ? $p->getHeureFin()->format('H:i') : '—';

            $rows .= <<<ROW
                        <tr>
                            <td><i class="fas fa-user-circle me-1 text-muted"></i> {$employe}</td>
                            <td>{$date}</td>
                            <td><span class="text-success fw-semibold">{$debut}</span></td>
                            <td><span class="text-danger fw-semibold">{$fin}</span></td>
                            <td><span class="badge {$badge['class']}">{$badge['label']}</span></td>
                        </tr>
            ROW;
        }

        if (!$rows) {
            $rows = '<tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>Aucun planning trouvé</td></tr>';
        }

        $html .= $rows;
        $html .= <<<HTML
                        </tbody>
                    </table>
                </div>
            </div>
        </body>
        </html>
        HTML;

        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }

    private function countPlannings(mixed $plannings): int
    {
        return is_array($plannings) ? count($plannings) : iterator_count($plannings);
    }
}