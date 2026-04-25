<?php

namespace App\Controller;

use App\Entity\Ressource;
use App\Form\RessourceType;
use App\Repository\RessourceRepository;
use App\Service\GrokService; // Changement ici
use App\Service\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RessourceController extends AbstractController
{
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Liste de l'inventaire et suggestions via Grok
     */
    #[Route('/ressource', name: 'ressource_index', methods: ['GET'])]
    public function index(RessourceRepository $repository, Request $request, GrokService $grokIA): Response 
    {
        $searchTerm = $request->query->get('q', '');
        $ressources = !empty($searchTerm) ? $repository->findBySearch($searchTerm) : $repository->findAll();

        $suggestions = [];
        if (!empty($ressources)) {
            try {
                // Utilisation du nouveau service Grok
                $suggestions = $grokIA->suggererProduits($ressources);
            } catch (\Exception $e) { 
                $suggestions = []; 
            }
        }

        $quantiteTotale = 0;
        $typesUniques = [];
        foreach ($ressources as $r) {
            $quantiteTotale += $r->getQuantite();
            $typesUniques[] = $r->getTypeRessource();
        }
        
        return $this->render('admin/Ressource/index.html.twig', [
            'ressources' => $ressources,
            'searchTerm' => $searchTerm,
            'stats' => [
                'totalTypes' => count(array_unique($typesUniques)),
                'quantiteTotale' => $quantiteTotale
            ],
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * API pour le Chatbot Assistant Stratix (Powered by Grok)
     */
    #[Route('/ressource/chat', name: 'ressource_chat', methods: ['POST'])]
    public function chat(Request $request, RessourceRepository $repo, GrokService $grok): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $question = $data['question'] ?? '';

        if (empty($question)) {
            return new JsonResponse(['reponse' => "Posez une question à Grok sur votre stock !"]);
        }

        $ressources = $repo->findAll();
        
        try {
            // Appel à Grok
            $reponse = $grok->repondreQuestionStock($question, $ressources);
        } catch (\Exception $e) {
            $reponse = "Désolé, erreur technique avec Grok : " . $e->getMessage();
        }

        return new JsonResponse(['reponse' => $reponse]);
    }

    /**
     * Formulaire Ajout / Edition
     */
    #[Route('/ressource/form/{id?}', name: 'ressource_form')]
    public function form(Ressource $ressource = null, Request $request, EntityManagerInterface $em): Response
    {
        if (!$ressource) {
            $ressource = new Ressource();
        }
        
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
     * Analyse via Python
     */
    #[Route('/ressource/{id}/analyser', name: 'app_ressource_analyser')]
    public function analyser(Ressource $ressource, Request $request): Response
    {
        return $this->render('admin/Ressource/import_analyse.html.twig', ['ressource' => $ressource]);
    }

    /**
     * Suppression
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
     * Export PDF
     */
    #[Route('/ressource/pdf', name: 'ressource_pdf')]
    public function generatePdfRessources(RessourceRepository $repository, PdfService $pdf): Response
    {
        $ressources = $repository->findAll();
        $html = $this->renderView('admin/Ressource/pdf.html.twig', ['ressources' => $ressources]);
        return $pdf->showPdfFile($html, 'Inventaire_Stratix_' . date('d-m-Y'));
    }

    /**
     * Taux de change (USD/TND)
     */
    private function getExchangeRate(): float
    {
        try {
            $response = $this->httpClient->request('GET', 'https://open.er-api.com/v6/latest/USD');
            $data = $response->toArray();
            return (float) ($data['rates']['TND'] ?? 3.12);
        } catch (\Exception $e) {
            return 3.12;
        }
    }
}