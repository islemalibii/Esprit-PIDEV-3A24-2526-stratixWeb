<?php

namespace App\Repository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use App\Entity\Projet;
use App\Entity\Utilisateur; 
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Projet>
 */
class ProjetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Projet::class);
    }

    public function findAllActive(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isArchived = :val')
            ->setParameter('val', false)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllArchived(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isArchived = :val')
            ->setParameter('val', true)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * ✅ CORRIGÉ : le OR est groupé entre parenthèses AVANT le AND isArchived
     * Sinon Doctrine génère : responsable = :user OR (m = :user AND isArchived = false)
     * ce qui exclut les projets où l'user est responsable mais archivés... ou pire,
     * inclut des projets archivés où il est responsable.
     *
     * @return Projet[]
     */
    public function findProjetsPourEmploye(Utilisateur $user): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.membres', 'm')
            ->where('(p.responsable = :user OR m = :user)')  // ← parenthèses explicites
            ->andWhere('p.isArchived = :archived')           // ← appliqué sur le groupe entier
            ->setParameter('user', $user)
            ->setParameter('archived', false)
            ->orderBy('p.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveWithFilters(?string $search, ?string $statut): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.isArchived = false OR p.isArchived IS NULL');

        if ($search) {
            $qb->andWhere('p.nom LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($statut) {
            $qb->andWhere('p.statut = :statut')
               ->setParameter('statut', $statut);
        }

        return $qb->orderBy('p.id', 'DESC');
    }

    public function findProjetsProchesEcheance(int $days = 7): array
    {
        $dateCible = new \DateTime();
        $dateCible->modify('+' . $days . ' days');

        $debutJour = (clone $dateCible)->setTime(0, 0, 0);
        $finJour   = (clone $dateCible)->setTime(23, 59, 59);

        return $this->createQueryBuilder('p')
            ->where('p.dateFin BETWEEN :debut AND :fin')
            ->andWhere('p.isArchived = :archived')
            ->setParameter('debut', $debutJour)
            ->setParameter('fin', $finJour)
            ->setParameter('archived', false)
            ->getQuery()
            ->getResult();
    }
}