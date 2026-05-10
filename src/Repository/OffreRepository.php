<?php

namespace App\Repository;

use App\Entity\Offre;
use App\Entity\Ressource;
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
     * @param \App\Entity\Ressource|int|string $ressource
     * @return Offre[]
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