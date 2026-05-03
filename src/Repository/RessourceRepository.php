<?php

namespace App\Repository;

use App\Entity\Ressource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ressource>
 */
class RessourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ressource::class);
    }

    /**
     * Recherche multicritère (Nom, Type, Fournisseur)
     * * @param string $term
     * @return Ressource[] Returns an array of Ressource objects
     */
    public function findBySearch(string $term): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.nom LIKE :term')
            ->orWhere('r.type_ressource LIKE :term')
            ->orWhere('r.fournisseur LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('r.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule la quantité totale directement en SQL
     */
    public function getTotalQuantite(): int
    {
        $result = $this->createQueryBuilder('r')
            ->select('SUM(r.quantite)')
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }
}