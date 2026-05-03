<?php

namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * @return Notification[]
     */
    public function findUnreadByUser(int $userId): array
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.userId = :userId')
            ->andWhere('n.isRead = false')
            ->orderBy('n.createdAt', 'DESC')
            ->setParameter('userId', $userId);
        
        $query = $qb->getQuery();
        $result = $query->getResult();
        
        /** @var Notification[] $result */
        return $result;
    }

    /**
     * @return Notification[]
     */
    public function findRecentByUser(int $userId, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.userId = :userId')
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('userId', $userId);
        
        $query = $qb->getQuery();
        $result = $query->getResult();
        
        /** @var Notification[] $result */
        return $result;
    }
}