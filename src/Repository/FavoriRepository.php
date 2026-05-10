<?php

namespace App\Repository;

use App\Entity\Favori;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favori>
 *
 * @method Favori|null find($id, $lockMode = null, $lockVersion = null)
 * @method Favori|null findOneBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null)
 * @method Favori[]    findAll()
 * @method Favori[]    findBy(array<string, mixed> $criteria, array<string, string>|null $orderBy = null, int|null $limit = null, int|null $offset = null)
 */
class FavoriRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favori::class);
    }
}