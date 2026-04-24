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
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RessourceController extends AbstractController
{
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Affiche l'inventaire et génère la suggestion de production via IA
     */
    #[Route('/ressource', name: 'ressource_index', methods: ['GET'])]
    public function index(RessourceRepository $repository, ImportLogRepository $importLogRepo, Request $request): Response 
    {
        $searchTerm = $request->query->get('q');
        $ressources = $searchTerm ? $repository->findBySearch($searchTerm) : $repository->findAll();

        // --- SECTION IA : SUGGESTION DE PRODUCTION GLOBALE ---
        $stocksData = [];
        foreach ($ressources as $r) {
            // On prépare les données pour le script Python (Nom -> Quantité et Nom -> Prix)
            $stocksData[$r->getNom()] = $r->getQuantite();
            // Note: Assurez-vous que l'entité Ressource possède bien le champ 'prix'
            $stocksData[$r->getNom() . '_prix'] = method_exists($r, 'getPrix') ? $r->getPrix() : 0; 
        }

        // Définition des "recettes" de produits à créer (Nomenclature/BOM)
        $configurations = [
            [
                'nom' => 'Kit Réseau Entreprise v2',
                'prix_vente' => 1500.0,
                'composants' => ['Routeur Cisco' => 1, 'Câble Cat6' => 20]
            ],
            [
                'nom' => 'Pack Maintenance Switch',
                'prix_vente' => 850.0,
                'composants' => ['Switch 24p' => 1, 'Câble Cat6' => 5]
            ]
        ];

        // Appel du script Python dédié à la suggestion de création
        $projectDir = $this->getParameter('kernel.project_dir');
        $processProd = new Process(['python', $projectDir . '/scripts/suggest_production.py']);
        $processProd->setInput(json_encode([
            'stocks' => $stocksData,
            'produits' => $configurations
        ]));
        $processProd->run();

        $suggestionsIA = $processProd->isSuccessful() ? json_decode($processProd->getOutput(), true) : [];
        $meilleureSuggestion = $suggestionsIA[0] ?? null;
        // ----------------------------------------------------

        // Statistiques de l'inventaire
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
            'suggestion' => $meilleureSuggestion, // Utilisé dans l'onglet Optimisation
        ]);
    }

    /**
     * Analyse une ressource spécifique par rapport à des catalogues CSV (Prix & Délais)
     */
    #[Route('/ressource/{id}/analyser', name: 'app_ressource_analyser')]
    public function analyser(Ressource $ressource, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $files = $request->files->get('csv_files');
            $tauxTND = $this->getExchangeRate();

            if ($files && is_array($files)) {
                $dataForAi = [];
                foreach ($files as $file) {
                    if ($file && ($handle = fopen($file->getRealPath(), "r")) !== FALSE) {
                        fgetcsv($handle); // Skip header
                        $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                        
                        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                            if (isset($data[0]) && strtolower(trim($data[0])) === strtolower(trim($ressource->getNom()))) {
                                $prixSource = (float)$data[1];
                                $prixEnDinar = $prixSource * $tauxTND;

                                $dataForAi[] = [
                                    'fournisseur' => $data[3] ?? $fileName, 
                                    'prix' => $prixEnDinar, 
                                    'delai' => (int)$data[2]
                                ];
                            }
                        }
                        fclose($handle);
                    }
                }

                if (empty($dataForAi)) {
                    $this->addFlash('warning', "Aucune offre trouvée dans les fichiers pour cette ressource.");
                    return $this->redirectToRoute('ressource_index');
                }

                $projectDir = $this->getParameter('kernel.project_dir');
                $process = new Process(['python', $projectDir . '/scripts/analyse_ia.py']);
                $process->setInput(json_encode($dataForAi));
                $process->run();

                $resultatsIA = $process->isSuccessful() ? json_decode($process->getOutput(), true) : $dataForAi;

                return $this->render('admin/Ressource/resultat_ia.html.twig', [
                    'ressource' => $ressource,
                    'resultats' => $resultatsIA,
                    'taux_applique' => $tauxTND
                ]);
            }
        }
        return $this->render('admin/Ressource/import_analyse.html.twig', ['ressource' => $ressource]);
    }

    /**
     * Récupère le taux de change USD vers TND via API externe
     */
    private function getExchangeRate(): float
    {
        try {
            $response = $this->httpClient->request('GET', 'https://open.er-api.com/v6/latest/USD');
            $data = $response->toArray();
            return (float) ($data['rates']['TND'] ?? 3.12);
        } catch (\Exception $e) {
            return 3.12; // Valeur par défaut
        }
    }

    /**
     * Formulaire d'ajout ou de modification de ressource
     */
    #[Route('/ressource/form/{id?}', name: 'ressource_form')]
    public function form(Ressource $ressource = null, Request $request, EntityManagerInterface $em): Response
    {
        if (!$ressource) $ressource = new Ressource();
        $form = $this->createForm(RessourceType::class, $ressource);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($ressource);
            $em->flush();
            $this->addFlash('success', 'La ressource a été enregistrée avec succès.');
            return $this->redirectToRoute('ressource_index');
        }

        return $this->render('admin/Ressource/form.html.twig', [
            'form' => $form->createView(),
            'editMode' => $ressource->getId() !== null,
            'ressource' => $ressource
        ]);
    }

    /**
     * Suppression d'une ressource
     */
    #[Route('/ressource/delete/{id}', name: 'ressource_delete', methods: ['POST'])]
    public function delete(Ressource $ressource, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ressource->getId(), $request->request->get('_token'))) {
            $em->remove($ressource);
            $em->flush();
            $this->addFlash('success', 'Ressource supprimée.');
        }
        return $this->redirectToRoute('ressource_index');
    }

    /**
     * Génération du PDF d'inventaire
     */
    #[Route('/ressource/pdf', name: 'ressource_pdf')]
    public function generatePdfRessources(RessourceRepository $repository, PdfService $pdf): Response
    {
        $ressources = $repository->findAll();
        $html = $this->renderView('admin/Ressource/pdf.html.twig', ['ressources' => $ressources]);
        return $pdf->showPdfFile($html, 'Inventaire_Ressources_Stratix_' . date('d-m-Y'));
    }

    /**
     * API pour Flutter : Export PDF en Base64
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

 #[Route('/ressource/ai-assistant', name: 'app_ressource_ai_assistant', methods: ['GET'])]
public function stratixAIAssistant(RessourceRepository $repo, GeminiService $geminiIA): Response 
{
    // On récupère toutes les ressources réelles de ta base de données
    $ressources = $repo->findAll();
    
    // On demande à l'IA d'analyser ces ressources spécifiques
    $suggestions = $geminiIA->suggererProduits($ressources);

    return $this->render('admin/Ressource/ai_assistant.html.twig', [
        'ressources' => $ressources,
        'suggestions' => $suggestions
    ]);
}
}