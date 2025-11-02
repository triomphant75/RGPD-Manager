<?php

namespace App\Repository;

use App\Entity\DeletionAudit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeletionAudit>
 */
class DeletionAuditRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeletionAudit::class);
    }

    /**
     * Vérifie si un email a déjà été supprimé
     *
     * @param string $email
     * @return bool
     */
    public function isEmailDeleted(string $email): bool
    {
        $emailHash = hash('sha256', $email);

        $count = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.emailHash = :emailHash')
            ->setParameter('emailHash', $emailHash)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Récupère les logs de suppression expirés (pour purge)
     *
     * @return DeletionAudit[]
     */
    public function findExpiredLogs(): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.retentionUntil < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les statistiques de suppressions
     *
     * @param \DateTimeInterface|null $from
     * @param \DateTimeInterface|null $to
     * @return array
     */
    public function getStatistics(?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('COUNT(d.id) as total');

        if ($from) {
            $qb->andWhere('d.deletionDate >= :from')
               ->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('d.deletionDate <= :to')
               ->setParameter('to', $to);
        }

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Récupère les logs par administrateur
     *
     * @param int $adminId
     * @return DeletionAudit[]
     */
    public function findByAdmin(int $adminId): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.deletedByAdmin = :adminId')
            ->setParameter('adminId', $adminId)
            ->orderBy('d.deletionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Purge les logs expirés
     *
     * @return int Nombre de logs supprimés
     */
    public function purgeExpiredLogs(): int
    {
        return $this->createQueryBuilder('d')
            ->delete()
            ->where('d.retentionUntil < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }
}
