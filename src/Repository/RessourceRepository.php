<?php

namespace App\Repository;

use App\Entity\Ressource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\ImportLogRepository;

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
     * Équivalent de ton filtrage dans le controller JavaFX
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
     * Si tu veux aussi calculer la quantité totale directement en SQL 
     * (plus performant que la boucle foreach dans le controller)
     */
    public function getTotalQuantite(): int
    {
        return $this->createQueryBuilder('r')
            ->select('SUM(r.quantite)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }
}