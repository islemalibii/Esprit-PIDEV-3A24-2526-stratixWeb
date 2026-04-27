<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    /**
     * @param string $term
     * @return Produit[] Returns an array of Produit objects
     */
    public function findBySearch(string $term): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.nom LIKE :t')
            ->orWhere('p.description LIKE :t')
            ->orWhere('p.categorie LIKE :t')
            ->setParameter('t', '%'.$term.'%')
            ->getQuery()
            ->getResult();
    }
}