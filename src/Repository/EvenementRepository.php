<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;


/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    
    public function findByArchiveStatus(bool $status): QueryBuilder
    { 
        return $this->createQueryBuilder('e')
            ->where('e.isArchived = :status')
            ->setParameter('status', $status);
    }

    public function findByArchiveStatusArray(bool $status): array
    { 
        return $this->createQueryBuilder('e')
            ->where('e.isArchived = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }

    
    public function searchByTitle(string $title): QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->where('e.titre LIKE :title')
            ->andWhere('e.isArchived = false')
            ->setParameter('title', '%'.$title.'%');
    }

    public function filterByType(string $type): QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->where('e.type_event = :type')
            ->andWhere('e.isArchived = false')
            ->setParameter('type', $type);
    }
    

    public function findVisibleForEmployees(): QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->where('e.statut IN (:allowed_status)')
            ->andWhere('e.isArchived = false')
            ->setParameter('allowed_status', ['planifier', 'terminer']);
    }


    public function filterByTypeForEmployee(string $type): QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->where('e.type_event = :type')
            ->andWhere('e.statut = :status')
            ->andWhere('e.isArchived = false')
            ->setParameter('type', $type)
            ->setParameter('status','planifier');
    }
    public function searchPlanifierByTitle(string $title): QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->where('e.statut = :status')
            ->andWhere('e.isArchived = false')
            ->andWhere('e.titre LIKE :title')
            ->setParameter('status', 'planifier')
            ->setParameter('title', '%' . $title . '%');
    }
    //    /**
    //     * @return Evenement[] Returns an array of Evenement objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Evenement
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
