<?php
// src/Controller/ParticipationController.php

namespace App\Controller;
use App\Entity\Utilisateur;
use App\Entity\Evenement;
use App\Service\ParticipationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Repository\ParticipationRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;


#[Route('/api/participation')]
class ParticipationController extends AbstractController
{
    public function __construct(private ParticipationService $participationService) {}

    #[Route('/join/{id}', name: 'participation_join', methods: ['POST'])]
    public function join(Evenement $evenement): JsonResponse
    {
        $user = $this->getUser();

    if (!$user instanceof Utilisateur) {
        return $this->json(['success' => false, 'message' => 'Vous devez être connecté.'], 401);
    }

    $result = $this->participationService->participate($evenement, $user->getEmail());

    return $this->json($result, $result['success'] ? 200 : 409);
    }

    #[Route('/cancel/{id}', name: 'participation_cancel', methods: ['POST'])]
    public function cancel(Evenement $evenement): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            return $this->json(['success' => false, 'message' => 'Vous devez être connecté.'], 401);
        }

        $result = $this->participationService->cancelParticipation($evenement, $user->getUserIdentifier());

        return $this->json($result, $result['success'] ? 200 : 409);
    }
    #[Route('/export/{id}', name: 'participation_export_excel', methods: ['GET'])]
    public function exportExcel(Evenement $evenement, ParticipationRepository $participationRepo): StreamedResponse
    {
        $participations = $participationRepo->findBy(['event_id' => $evenement->getId()]);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Participants');

        $sheet->setCellValue('A1', 'Email');
        $sheet->setCellValue('B1', 'Date de participation');
        $sheet->setCellValue('C1', 'Événement');

        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        $sheet->getStyle('A1:C1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF7C3AED');
        $sheet->getStyle('A1:C1')->getFont()->getColor()->setARGB('FFFFFFFF');

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);

        $row = 2;
        foreach ($participations as $p) {
            $sheet->setCellValue('A' . $row, $p->getUserEmail());
            $sheet->setCellValue('B' . $row, $p->getParticipationDate()->format('d/m/Y H:i'));
            $sheet->setCellValue('C' . $row, $evenement->getTitre());
            $row++;
        }

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="participants-' . $evenement->getId() . '.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}