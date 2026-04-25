<?php

namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findUnreadByUser(int $userId): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.userId = :userId')
            ->andWhere('n.isRead = false')
            ->orderBy('n.createdAt', 'DESC')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }

    public function findRecentByUser(int $userId, int $limit = 10): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.userId = :userId')
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }
}