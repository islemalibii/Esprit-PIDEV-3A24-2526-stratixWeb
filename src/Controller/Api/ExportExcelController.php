<?php

namespace App\Controller\Api;

use App\Entity\Planning;
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
        $typeShift = $request->query->get('type_shift');
        $employeId = $request->query->get('employe_id');
        $download  = $request->query->get('download');

        $typeShiftStr = is_string($typeShift) ? $typeShift : '';
        $employeIdInt = is_numeric($employeId) ? (int) $employeId : null;

        /** @var Planning[] $plannings */
        $plannings = $this->planningRepository->findAll();

        if ($typeShiftStr !== '') {
            $plannings = array_values(array_filter($plannings, fn(Planning $p) => $p->getTypeShift() === $typeShiftStr));
        }
        if ($employeIdInt !== null) {
            $plannings = array_values(array_filter($plannings, fn(Planning $p) => $p->getEmployeId() === $employeIdInt));
        }

        // Garantit un tableau Planning[] avec clés entières
        $plannings = array_values($plannings);

        /** @var array<int, string> $employes */
        $employes = [];
        foreach ($this->utilisateurRepository->findAll() as $u) {
            $id = $u->getId();
            if (is_int($id)) {
                $employes[$id] = $u->getPrenom() . ' ' . $u->getNom();
            }
        }

        if ($download !== null) {
            return $this->generateExcelFile($plannings, $employes);
        }

        /** @var array<string, array{label: string, class: string}> $shiftBadges */
        $shiftBadges = [
            'MATIN' => ['label' => '☀️ MATIN',  'class' => 'bg-info'],
            'SOIR'  => ['label' => '🌆 SOIR',   'class' => 'bg-warning'],
            'NUIT'  => ['label' => '🌙 NUIT',   'class' => 'bg-dark'],
            'CONGE' => ['label' => '🏖️ CONGÉ', 'class' => 'bg-warning text-dark'],
            'RTT'   => ['label' => '📅 RTT',    'class' => 'bg-secondary'],
        ];

        $titre = 'Tous les plannings';
        if ($typeShiftStr !== '' && isset($shiftBadges[$typeShiftStr])) {
            $titre = 'Plannings — ' . $shiftBadges[$typeShiftStr]['label'];
        } elseif ($employeIdInt !== null && isset($employes[$employeIdInt])) {
            $titre = 'Plannings de ' . $employes[$employeIdInt];
        }

        $separator   = str_contains($request->getRequestUri(), '?') ? '&' : '?';
        $downloadUrl = $request->getRequestUri() . $separator . 'download=1';

        $html = $this->generateHtmlTable($titre, $plannings, $employes, $shiftBadges, $downloadUrl);

        return new Response($html, 200, ['Content-Type' => 'text/html']);
    }

    /**
     * @param Planning[]         $plannings
     * @param array<int, string> $employes
     */
    private function generateExcelFile(array $plannings, array $employes): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Plannings');

        $sheet->setCellValue('A1', 'Employé');
        $sheet->setCellValue('B1', 'Date');
        $sheet->setCellValue('C1', 'Heure Début');
        $sheet->setCellValue('D1', 'Heure Fin');
        $sheet->setCellValue('E1', 'Type Shift');

        $row = 2;
        foreach ($plannings as $p) {
            $empId = $p->getEmployeId();
            $sheet->setCellValue('A' . $row, ($empId !== null && isset($employes[$empId])) ? $employes[$empId] : 'Non assigné');

            $date = $p->getDate();
            $sheet->setCellValue('B' . $row, $date !== null ? $date->format('d/m/Y') : '');

            $heureDebut = $p->getHeureDebut();
            $sheet->setCellValue('C' . $row, $heureDebut !== null ? $heureDebut->format('H:i') : '');

            $heureFin = $p->getHeureFin();
            $sheet->setCellValue('D' . $row, $heureFin !== null ? $heureFin->format('H:i') : '');

            $sheet->setCellValue('E' . $row, $p->getTypeShift() ?? '');
            $row++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension((string) $col)->setAutoSize(true);
        }

        $writer   = new Xlsx($spreadsheet);
        $fileName = 'plannings_' . date('Y-m-d') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save((string) $tempFile);

        return $this->file((string) $tempFile, $fileName, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    /**
     * @param Planning[]                                         $plannings
     * @param array<int, string>                                 $employes
     * @param array<string, array{label: string, class: string}> $shiftBadges
     */
    private function generateHtmlTable(
        string $titre,
        array $plannings,
        array $employes,
        array $shiftBadges,
        string $downloadUrl
    ): string {
        $count = count($plannings);

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
                            <i class="fas fa-list me-1"></i> {$count} entrée(s)
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
            $shift   = $p->getTypeShift() ?? 'AUTRE';
            $badge   = $shiftBadges[$shift] ?? ['label' => $shift, 'class' => 'bg-secondary'];
            $empId   = $p->getEmployeId();
            $employe = htmlspecialchars(($empId !== null && isset($employes[$empId])) ? $employes[$empId] : 'Non assigné');

            $dateObj = $p->getDate();
            $date    = $dateObj !== null ? $dateObj->format('d/m/Y') : '';

            $heureDebutObj = $p->getHeureDebut();
            $debut         = $heureDebutObj !== null ? $heureDebutObj->format('H:i') : '—';

            $heureFinObj = $p->getHeureFin();
            $fin         = $heureFinObj !== null ? $heureFinObj->format('H:i') : '—';

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

        if ($rows === '') {
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

        return $html;
    }
}
