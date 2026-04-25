<?php

namespace App\Repository;

use App\Entity\Offre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Offre>
 */
class OffreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offre::class);
    }

    /**
     * Optionnel : Une méthode personnalisée pour trouver les offres 
     * triées par prix pour une ressource donnée
     */
    public function findByRessourceOrderByPrix($ressource): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.ressource = :res')
            ->setParameter('res', $ressource)
            ->orderBy('o.prix', 'ASC')
            ->getQuery()
            ->getResult();
    }
}