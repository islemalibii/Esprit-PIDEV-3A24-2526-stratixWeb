<?php

// src/Controller/ImportController.php
namespace App\Controller;

use App\Form\ImportCatalogueType;
use App\Service\ImportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ImportController extends AbstractController
{
    #[Route('/admin/import-catalogue', name: 'app_import_catalogue')]
    public function import(Request $request, ImportService $importService): Response
    {
        $form = $this->createForm(ImportCatalogueType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $fournisseur = $form->get('fournisseur')->getData();
            $file = $form->get('fichier')->getData();

            if ($file) {
                try {
                    $importService->importCatalogue($file->getPathname(), $fournisseur->getId());
                    $this->addFlash('success', 'Le catalogue de ' . $fournisseur->getNom() . ' a été importé avec succès !');
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Erreur lors de l\'importation : ' . $e->getMessage());
                }
            }

            return $this->redirectToRoute('app_import_catalogue');
        }

        return $this->render('import/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}