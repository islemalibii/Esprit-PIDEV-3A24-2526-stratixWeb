<?php

namespace App\Repository;

use App\Entity\UserBadge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserBadgeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBadge::class);
    }

    public function findUserBadges(int $userId): array
    {
        return $this->createQueryBuilder('ub')
            ->where('ub.userId = :userId')
            ->orderBy('ub.obtenuLe', 'DESC')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }
}