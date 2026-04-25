<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Form\ProjetType;
use App\Repository\ProjetRepository;
use App\Service\AiResumeService;
use Doctrine\ORM\EntityManagerInterface;
use Smalot\PdfParser\Parser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\Favori;
use App\Repository\FavoriRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Phase;

#[Route('/projet')]
class ProjetController extends AbstractController
{
    // ─────────────────────────────────────────────
    //  LISTE (ADMIN) - Avec Pagination
    // ─────────────────────────────────────────────
    #[Route('/', name: 'app_projet_index', methods: ['GET'])]
    public function index(Request $request, ProjetRepository $repo, PaginatorInterface $paginator, FavoriRepository $favoriRepo): Response
    {
        $search = $request->query->get('search');
        $statut = $request->query->get('statut');

        $query = $repo->findActiveWithFilters($search, $statut);

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            6 
        );

        $userFavorisIds = [];
        if ($this->getUser()) {
            $favoris = $favoriRepo->findBy(['utilisateur' => $this->getUser()]);
            foreach ($favoris as $f) {
                $userFavorisIds[] = $f->getProjet()->getId();
            }
        }

        return $this->render('admin/Projet/listeProjets.html.twig', [
            'projets'          => $pagination,
            'currentSearch'    => $search,
            'currentStatut'    => $statut,
            'user_favoris_ids' => $userFavorisIds,
        ]);
    }

    // ─────────────────────────────────────────────
    //  CRÉER UN PROJET
    // ─────────────────────────────────────────────
    #[Route('/new', name: 'app_projet_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $projet = new Projet();
        $form = $this->createForm(ProjetType::class, $projet, [
            'validation_groups' => ['Default', 'registration'],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $file */
            $file = $form->get('cahierDesChargesFile')->getData();

            if ($file) {
                $uploadDir   = $this->getParameter('kernel.project_dir') . '/public/uploads/cahiers';
                $newFilename = uniqid('cdc_') . '_' . time() . '.' . $file->guessExtension();
                $file->move($uploadDir, $newFilename);
                $projet->setCahierDesCharges($newFilename);
            }

            if (!$projet->getStatut()) {
                $projet->setStatut('Planifié');
            }

            $projet->setIsArchived(false);
            $em->persist($projet);
            $em->flush();

            $this->addFlash('success', '✅ Projet "' . $projet->getNom() . '" créé !');
            return $this->redirectToRoute('app_projet_index');
        }

        return $this->render('admin/Projet/ajouterProjet.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ─────────────────────────────────────────────
    //  MODIFIER UN PROJET
    // ─────────────────────────────────────────────
    #[Route('/{id}/edit', name: 'app_projet_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Projet $projet, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProjetType::class, $projet, [
            'validation_groups' => ['Default'],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $file */
            $file = $form->get('cahierDesChargesFile')->getData();

            if ($file) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/cahiers';
                if ($projet->getCahierDesCharges()) {
                    $oldPath = $uploadDir . '/' . $projet->getCahierDesCharges();
                    if (file_exists($oldPath)) { unlink($oldPath); }
                }
                $newFilename = uniqid('cdc_') . '_' . time() . '.' . $file->guessExtension();
                $file->move($uploadDir, $newFilename);
                $projet->setCahierDesCharges($newFilename);
            }

            $em->flush();
            $this->addFlash('success', '✅ Projet modifié !');
            return $this->redirectToRoute('app_projet_index');
        }

        return $this->render('admin/Projet/modifierProjet.html.twig', [
            'projet' => $projet,
            'form'   => $form->createView(),
        ]);
    }

    // ─────────────────────────────────────────────
    //  ARCHIVES
    // ─────────────────────────────────────────────
    #[Route('/archives', name: 'app_projet_archives', methods: ['GET'])]
    public function archives(ProjetRepository $repo): Response
    {
        return $this->render('admin/Projet/listeArchives.html.twig', [
            'projets' => $repo->findAllArchived(),
        ]);
    }

    #[Route('/{id}/archiver', name: 'app_projet_archive_action')]
    public function archiver(Projet $p, EntityManagerInterface $em): Response
    {
        $p->setIsArchived(true);
        $em->flush();
        $this->addFlash('success', 'Projet archivé.');
        return $this->redirectToRoute('app_projet_index');
    }

    #[Route('/unarchive/{id}', name: 'app_projet_unarchive_action')]
    public function unarchive(Projet $projet, EntityManagerInterface $em): Response
    {
        $projet->setIsArchived(false); 
        $em->flush();
        $this->addFlash('success', 'Projet restauré.');
        return $this->redirectToRoute('app_projet_archives');
    }

    // ─────────────────────────────────────────────
    //  DÉTAILS & CHAT
    // ─────────────────────────────────────────────
    #[Route('/{id}/show', name: 'app_projet_show', methods: ['GET'])]
    public function show(Projet $projet): Response
    {
        return $this->render('admin/Projet/detailsProjet.html.twig', [
            'projet' => $projet
        ]);
    }

    #[Route('/{id}/chat', name: 'app_projet_chat', methods: ['GET'])]
    public function chat(Projet $projet): Response
    {
        return $this->render('admin/Projet/chat.html.twig', [
            'projet' => $projet
        ]);
    }

    // ─────────────────────────────────────────────
    //  IA : ANALYSE DU CAHIER DES CHARGES
    // ─────────────────────────────────────────────
    #[Route('/employee/projet/{id}/analyser-ia', name: 'app_projet_analyser_ia', methods: ['GET'])]
    public function analyserProjetExistant(Projet $projet, AiResumeService $aiService): JsonResponse
    {
        $fileName = $projet->getCahierDesCharges();
        if (!$fileName) return $this->json(['error' => 'Aucun fichier trouvé.'], 404);

        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/cahiers/' . $fileName;
        if (!file_exists($filePath)) return $this->json(['error' => 'Fichier introuvable.'], 404);

        try {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $contenu = ($extension === 'pdf') ? (new Parser())->parseFile($filePath)->getText() : file_get_contents($filePath);
            
            $result = $aiService->generateSummary(mb_substr(trim($contenu), 0, 3000));
            
            if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $result, $matches)) {
                $data = json_decode($matches[0], true);
                return $this->json([
                    'success' => true,
                    'resume_court' => $data['resume_court'] ?? '',
                    'resume_detaille' => $data['resume_detaille'] ?? ''
                ]);
            }
            return $this->json(['error' => 'Format IA invalide'], 500);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────
    //  VUES EMPLOYÉ
    // ─────────────────────────────────────────────
    #[Route('/employee/mes-projets', name: 'app_projet_employee_index')]
    public function indexEmployee(ProjetRepository $repo, FavoriRepository $favoriRepo): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $userFavorisIds = [];
        $favoris = $favoriRepo->findBy(['utilisateur' => $user]);
        foreach ($favoris as $f) {
            $userFavorisIds[] = $f->getProjet()->getId();
        }

        return $this->render('employee/projetEmploye.html.twig', [
            'projets' => $repo->findProjetsPourEmploye($user),
            'user_favoris_ids' => $userFavorisIds,
        ]);
    }

    #[Route('/employee/projet/{id}/show', name: 'app_projet_employe_show', methods: ['GET'])]
    public function showEmployee(Projet $projet): Response
    {
        $user = $this->getUser();

        if (!$projet->getMembres()->contains($user)) {
            $this->addFlash('danger', 'Accès refusé : vous ne faites pas partie de ce projet.');
            return $this->redirectToRoute('app_projet_employee_index');
        }

        return $this->render('employee/employeProjetDetails.html.twig', [
            'projet' => $projet,
            'phases' => $projet->getPhases(), // Changé de getSprints() à getPhases()
        ]);
    }

    // ─────────────────────────────────────────────
    //  FAVORIS
    // ─────────────────────────────────────────────
    #[Route('/mes-favoris', name: 'app_projet_favoris_liste')]
    public function listeFavoris(FavoriRepository $favoriRepo): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $favoris = $favoriRepo->findBy(['utilisateur' => $user]);

        return $this->render('admin/Projet/favoris_liste.html.twig', [
            'favoris' => $favoris
        ]);
    }

    #[Route('/favori/{id}', name: 'projet_toggle_favori', methods: ['GET', 'POST'])]
    public function toggleFavori(Projet $projet, EntityManagerInterface $em, FavoriRepository $repo): Response 
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['message' => 'Non connecté'], 403);

        $favori = $repo->findOneBy(['utilisateur' => $user, 'projet' => $projet]);

        if ($favori) {
            $em->remove($favori);
            $isFavorite = false;
        } else {
            $favori = new Favori();
            $favori->setUtilisateur($user);
            $favori->setProjet($projet);
            $em->persist($favori);
            $isFavorite = true;
        }

        $em->flush();
        return $this->json(['isFavorite' => $isFavorite]);
    }

    #[Route('/employee/mes-favoris', name: 'app_projet_employee_favoris')]
    public function favorisEmployee(FavoriRepository $favoriRepo): Response
    {
        $user = $this->getUser();
        if (!$user) return $this->redirectToRoute('app_login');

        $favoris = $favoriRepo->findBy(['utilisateur' => $user]);

        return $this->render('employee/favoris_liste.html.twig', [
            'favoris' => $favoris
        ]);
    }

    // ─────────────────────────────────────────────
    //  EXPORT PDF
    // ─────────────────────────────────────────────
    #[Route('/employee/projet/{id}/export-pdf', name: 'app_projet_export_pdf', methods: ['GET'])]
    public function exportPdf(Projet $projet): Response
    {
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($pdfOptions);
        $html = $this->renderView('employee/projet_pdf.html.twig', [
            'projet' => $projet,
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $safeFilename = str_replace([' ', '/', '\\'], '_', $projet->getNom());
        $fileName = 'Stratix_Rapport_' . $safeFilename . '_' . date('Y-m-d') . '.pdf';

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
        ]);
    }

    // ─────────────────────────────────────────────
    //  CALENDRIER
    // ─────────────────────────────────────────────
    #[Route('/api/projets/calendar', name: 'api_projets_calendar', methods: ['GET'])]
    public function getCalendarEvents(ProjetRepository $projetRepository): JsonResponse
    {
        $projets = $projetRepository->findBy(['isArchived' => false]); 

        $events = [];
        foreach ($projets as $projet) {
            if ($projet->getDateDebut() && $projet->getDateFin()) {
                $events[] = [
                    'id' => $projet->getId(),
                    'title' => $projet->getNom(),
                    'start' => $projet->getDateDebut()->format('Y-m-d'),
                    'end' => $projet->getDateFin()->modify('+1 day')->format('Y-m-d'),
                    'backgroundColor' => $this->getColorByStatut($projet->getStatut() ?? 'default'),
                    'borderColor' => $this->getColorByStatut($projet->getStatut() ?? 'default'),
                    'url' => $this->generateUrl('app_projet_show', ['id' => $projet->getId()]),
                    'allDay' => true
                ];
            }
        }
        return new JsonResponse($events);
    }

    private function getColorByStatut(string $statut): string
    {
        return match ($statut) {
            'Planifié' => '#f39c12',
            'En cours' => '#3498db',
            'Terminé'  => '#2ecc71',
            'Annulé'   => '#e74c3c',
            default    => '#95a5a6',
        };
    }
}