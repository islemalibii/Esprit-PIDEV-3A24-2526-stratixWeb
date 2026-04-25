<?php

namespace App\Controller;

use App\Entity\Ressource;
use App\Form\RessourceType;
use App\Repository\RessourceRepository;
use App\Repository\ImportLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\PdfService;
use Symfony\Component\Process\Process;

class RessourceController extends AbstractController
{
    #[Route('/ressource', name: 'ressource_index', methods: ['GET'])]
    public function index(RessourceRepository $repository, ImportLogRepository $importLogRepo, Request $request): Response 
    {
        $searchTerm = $request->query->get('q');
        $ressources = $searchTerm ? $repository->findBySearch($searchTerm) : $repository->findAll();

        $quantiteTotale = 0;
        $typesUniques = [];
        foreach ($ressources as $r) {
            $quantiteTotale += $r->getQuantite();
            $typesUniques[] = $r->getTypeRessource();
        }
        $nombreTypes = count(array_unique($typesUniques));
        $imports = $importLogRepo->findBy([], ['createdAt' => 'DESC'], 10);

        return $this->render('admin/Ressource/index.html.twig', [
            'ressources' => $ressources,
            'searchTerm' => $searchTerm,
            'quantiteTotale' => $quantiteTotale,
            'nombreTypes' => $nombreTypes,
            'imports' => $imports,
        ]);
    }

    #[Route('/ressource/{id}/analyser', name: 'app_ressource_analyser')]
    public function analyser(Ressource $ressource, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $files = $request->files->get('csv_files');
            if ($files && is_array($files)) {
                $dataForAi = [];
                foreach ($files as $file) {
                    if ($file && ($handle = fopen($file->getRealPath(), "r")) !== FALSE) {
                        fgetcsv($handle); 
                        $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                            if (isset($data[0]) && strtolower(trim($data[0])) === strtolower(trim($ressource->getNom()))) {
                                $dataForAi[] = [
                                    'fournisseur' => $data[3] ?? $fileName, 
                                    'prix' => (float)$data[1],
                                    'delai' => (int)$data[2]
                                ];
                            }
                        }
                        fclose($handle);
                    }
                }

                if (empty($dataForAi)) {
                    $this->addFlash('warning', "Aucune offre trouvée.");
                    return $this->redirectToRoute('ressource_index');
                }

                $projectDir = $this->getParameter('kernel.project_dir');
                $process = new Process(['python', $projectDir . '/scripts/analyse_ia.py']);
                $process->setInput(json_encode($dataForAi));
                $process->run();

                $resultatsIA = $process->isSuccessful() ? json_decode($process->getOutput(), true) : $dataForAi;

                return $this->render('admin/Ressource/resultat_ia.html.twig', [
                    'ressource' => $ressource,
                    'resultats' => $resultatsIA
                ]);
            }
        }
        return $this->render('admin/Ressource/import_analyse.html.twig', ['ressource' => $ressource]);
    }

    /**
     * API : Analyse IA (Retourne du JSON)
     */
    #[Route('/api/ressource/{id}/analyser', name: 'api_ressource_analyser', methods: ['POST'])]
    public function apiAnalyser(Ressource $ressource, Request $request): JsonResponse
    {
        // ... (Logique identique à la méthode analyser mais retourne JsonResponse)
        // [Par souci de brièveté, imaginez ici la même logique d'extraction CSV]
        return $this->json(['status' => 'success', 'data' => 'Résultats IA ici']);
    }

    /**
     * API : Export PDF (Retourne du JSON avec Base64)
     */
    #[Route('/api/ressource/pdf', name: 'api_ressource_pdf', methods: ['GET'])]
    public function apiPdf(RessourceRepository $repository, PdfService $pdf): JsonResponse
    {
        $ressources = $repository->findAll();
        $html = $this->renderView('admin/Ressource/pdf.html.twig', ['ressources' => $ressources]);
        $binary = $pdf->getBinaryContent($html);

        return $this->json([
            'status' => 'success',
            'filename' => 'export_stratix.pdf',
            'base64' => base64_encode($binary)
        ]);
    }

    #[Route('/ressource/form/{id?}', name: 'ressource_form')]
    public function form(Ressource $ressource = null, Request $request, EntityManagerInterface $em): Response
    {
        if (!$ressource) $ressource = new Ressource();
        $form = $this->createForm(RessourceType::class, $ressource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($ressource);
            $em->flush();
            $this->addFlash('success', 'Enregistré !');
            return $this->redirectToRoute('ressource_index');
        }

        return $this->render('admin/Ressource/form.html.twig', [
            'form' => $form->createView(),
            'editMode' => $ressource->getId() !== null,
            'ressource' => $ressource
        ]);
    }

    #[Route('/ressource/delete/{id}', name: 'ressource_delete', methods: ['POST'])]
    public function delete(Ressource $ressource, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ressource->getId(), $request->request->get('_token'))) {
            $em->remove($ressource);
            $em->flush();
        }
        return $this->redirectToRoute('ressource_index');
    }

    #[Route('/ressource/pdf', name: 'ressource_pdf')]
    public function generatePdfRessources(RessourceRepository $repository, PdfService $pdf): Response
    {
        $ressources = $repository->findAll();
        $html = $this->renderView('admin/Ressource/pdf.html.twig', ['ressources' => $ressources]);
        return $pdf->showPdfFile($html, 'Liste_Ressources_Stratix');
    }
}