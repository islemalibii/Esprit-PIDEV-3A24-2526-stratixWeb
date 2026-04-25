<?php

namespace App\Repository;

use App\Entity\Phase; // Importe l'entité Phase
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Phase>
 */
class PhaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        // On lie le repository à l'entité Phase
        parent::__construct($registry, Phase::class); 
    }

    /**
     * Exemple de méthode personnalisée pour STRATIX :
     * Récupérer les phases d'un projet triées par date de début
     */
    public function findByProjetOrdered($projetId)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.projet = :val')
            ->setParameter('val', $projetId)
            ->orderBy('p.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }
}