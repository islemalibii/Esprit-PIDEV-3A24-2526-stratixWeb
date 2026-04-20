<?php

namespace App\Repository;

use App\Entity\EventFeedback;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EventFeedback>
 */
class EventFeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventFeedback::class);
    }

    // src/Repository/EventFeedbackRepository.php

    public function findFeedbackEventIds(string $userEmail): array
    {
        $results = $this->createQueryBuilder('f')
            ->select('IDENTITY(f.evenement) as event_id')
            ->where('f.user_email = :email') 
            ->setParameter('email', $userEmail)
            ->getQuery()
            ->getArrayResult();

        return array_column($results, 'event_id');
    }
    //    /**
    //     * @return EventFeedback[] Returns an array of EventFeedback objects
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

    //    public function findOneBySomeField($value): ?EventFeedback
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
