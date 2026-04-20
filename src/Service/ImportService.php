<?php

namespace App\Service;

use App\Entity\Offre;
use App\Repository\FournisseurRepository;
use App\Repository\RessourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportService
{
    public function __construct(
        private EntityManagerInterface $em,
        private FournisseurRepository $fournisseurRepo,
        private RessourceRepository $ressourceRepo
    ) {}

    public function importCatalogue(string $filename, int $fournisseurId): void
    {
        $spreadsheet = IOFactory::load($filename);
        $data = $spreadsheet->getActiveSheet()->toArray();
        $fournisseur = $this->fournisseurRepo->find($fournisseurId);

        foreach ($data as $key => $row) {
            if ($key === 0) continue; // On saute l'entête

            $ressource = $this->ressourceRepo->findOneBy(['nom' => $row[0]]);
            if ($ressource && $fournisseur) {
                $offre = new Offre();
                $offre->setRessource($ressource);
                $offre->setFournisseur($fournisseur);
                $offre->setPrix((float)$row[1]);
                $offre->setDelaiTransportJours((int)$row[2]);
                $offre->setDateOffre(new \DateTime());

                $this->em->persist($offre);
            }
        }
        $this->em->flush();
    }
}