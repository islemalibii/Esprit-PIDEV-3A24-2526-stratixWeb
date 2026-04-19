<?php

namespace App\Command;

use App\Entity\Offre;
use App\Entity\ImportLog;
use App\Repository\FournisseurRepository;
use App\Repository\RessourceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:fetch-emails',
    description: 'Simule la récupération des catalogues (Sans IMAP)',
)]
class FetchEmailsCommand extends Command
{
    private $params;
    private $fournisseurRepo;
    private $ressourceRepo;
    private $em;

    public function __construct(ParameterBagInterface $params, FournisseurRepository $fournisseurRepo, RessourceRepository $ressourceRepo, EntityManagerInterface $em)
    {
        parent::__construct();
        $this->params = $params;
        $this->fournisseurRepo = $fournisseurRepo;
        $this->ressourceRepo = $ressourceRepo;
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $uploadDir = $this->params->get('catalogues_directory');

        $io->title('Stratix - Importateur de Catalogues (Mode Local)');

        // Liste des fichiers CSV dans le dossier
        $files = glob($uploadDir . '/*.csv');

        if (empty($files)) {
            $io->warning("Aucun fichier CSV trouvé dans : $uploadDir");
            $io->info("Dépose tes fichiers (carte esp32, cables) dans ce dossier pour tester.");
            return Command::SUCCESS;
        }

        foreach ($files as $filePath) {
            $fileName = basename($filePath);
            
            // On détermine le fournisseur selon le nom du fichier pour le test
            // Par exemple: si le fichier contient 'quincaillerie', c'est rahmadjebbi67
            $emailTest = (strpos($fileName, 'quincaillerie') !== false) 
                ? 'rahmadjebbi67@gmail.com' 
                : 'djebbi.Rahma@esprit.tn';

            $fournisseur = $this->fournisseurRepo->findOneBy(['email' => $emailTest]);

            if (!$fournisseur) {
                $io->error("Fournisseur avec l'email $emailTest non trouvé. Crée-le en base !");
                continue;
            }

            $this->importCsvData($filePath, $fournisseur, $io);

            // Log pour l'interface
            $log = new ImportLog();
            $log->setFileName($fileName);
            $log->setSenderEmail($fournisseur->getEmail());
            $log->setStatus('SUCCESS');
            $this->em->persist($log);
            
            $io->success("Importé : $fileName pour " . $fournisseur->getNom());
        }

        $this->em->flush();
        return Command::SUCCESS;
    }

    private function importCsvData(string $filePath, $fournisseur, SymfonyStyle $io): void
    {
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            fgetcsv($handle, 1000, ","); // Sauter l'entête
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) < 3) continue;
                
                $ressource = $this->ressourceRepo->findOneBy(['nom' => $data[0]]);
                if ($ressource) {
                    $offre = new Offre();
                    $offre->setFournisseur($fournisseur);
                    $offre->setRessource($ressource);
                    $offre->setPrix((float)$data[1]);
                    $offre->setDelaiTransportJours((int)$data[2]);
                    $offre->setDateOffre(new \DateTime());
                    $this->em->persist($offre);
                }
            }
            fclose($handle);
        }
    }
}