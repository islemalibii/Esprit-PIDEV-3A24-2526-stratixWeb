<?php
 
namespace App\Repository;
 
use App\Entity\Tache;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
 
/**
 * @extends ServiceEntityRepository<Tache>
 */
class TacheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tache::class);
    }
 
    /**
     * Real multi-criteria search in the database
     */
    public function search(string $search = '', string $statut = '', string $priorite = ''): array
    {
        $qb = $this->createQueryBuilder('t');
 
        if (!empty($search)) {
            $qb->andWhere('t.titre LIKE :search OR t.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
 
        if (!empty($statut)) {
            $qb->andWhere('t.statut = :statut')
               ->setParameter('statut', $statut);
        }
 
        if (!empty($priorite)) {
            $qb->andWhere('t.priorite = :priorite')
               ->setParameter('priorite', $priorite);
        }
 
        $qb->orderBy('t.deadline', 'ASC');
 
        return $qb->getQuery()->getResult();
    }
}
 