<?php

namespace App\Repository;

use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    public function getAdvancedSearchQueryBuilder(
        ?string $keyword = null,
        ?string $categorie = null,
        ?bool $archive = false,
        ?float $budgetMin = null,
        ?float $budgetMax = null,
        ?\DateTime $dateStartAfter = null,
        ?\DateTime $dateEndBefore = null
    ) {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.categorie', 'c');

        if ($archive !== null) {
            $qb->andWhere('s.archive = :archive')
               ->setParameter('archive', $archive);
        }

        if (!empty($keyword)) {
            $qb->andWhere('s.titre LIKE :kw OR s.description LIKE :kw')
               ->setParameter('kw', '%' . $keyword . '%');
        }

        if (!empty($categorie)) {
            $qb->andWhere('c.nom = :cat')
               ->setParameter('cat', $categorie);
        }

        if ($budgetMin !== null) {
            $qb->andWhere('s.budget >= :min')
               ->setParameter('min', $budgetMin);
        }

        if ($budgetMax !== null) {
            $qb->andWhere('s.budget <= :max')
               ->setParameter('max', $budgetMax);
        }

        if ($dateStartAfter !== null) {
            $qb->andWhere('s.date_debut >= :startAfter')
               ->setParameter('startAfter', $dateStartAfter);
        }

        if ($dateEndBefore !== null) {
            $qb->andWhere('s.date_fin <= :endBefore')
               ->setParameter('endBefore', $dateEndBefore);
        }

        $qb->orderBy('s.id', 'DESC');

        return $qb;
    }
}