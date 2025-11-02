<?php

namespace App\Service;

use App\Entity\DeletionAudit;
use App\Entity\User;
use App\Repository\DeletionAuditRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service pour gérer les logs d'audit des suppressions d'utilisateurs
 * Conformité RGPD Article 17 - Droit à l'effacement
 */
class DeletionAuditService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DeletionAuditRepository $deletionAuditRepository,
        private LoggerInterface $logger,
        private RequestStack $requestStack
    ) {
    }

    /**
     * Crée un log d'audit pour une suppression d'utilisateur
     *
     * @param User $deletedUser L'utilisateur supprimé
     * @param User $admin L'administrateur qui effectue la suppression
     * @param string|null $reason La raison de la suppression
     * @param array|null $metadata Métadonnées additionnelles (nombre de traitements, etc.)
     * @return DeletionAudit
     */
    public function logDeletion(
        User $deletedUser,
        User $admin,
        ?string $reason = null,
        ?array $metadata = null
    ): DeletionAudit {
        $audit = new DeletionAudit();

        // Hash de l'email pour éviter les recréations sans stocker l'email
        $emailHash = hash('sha256', $deletedUser->getEmail());
        $audit->setEmailHash($emailHash);

        // Identifiant anonymisé
        $anonymizedId = 'USER_' . $deletedUser->getId();
        $audit->setUserIdAnonymized($anonymizedId);

        // Administrateur qui a effectué la suppression
        $audit->setDeletedByAdmin($admin);

        // Raison de la suppression
        $audit->setDeletionReason($reason);

        // Adresse IP
        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $audit->setIpAddress($request->getClientIp());
        }

        // Métadonnées
        if ($metadata === null) {
            $metadata = [];
        }

        // Ajouter des métadonnées par défaut
        $metadata['user_id'] = $deletedUser->getId();
        $metadata['user_email_domain'] = substr(strrchr($deletedUser->getEmail(), "@"), 1);
        $metadata['user_roles'] = $deletedUser->getRoles();
        $metadata['treatments_count'] = $deletedUser->getTreatments()->count();
        $metadata['notifications_count'] = $deletedUser->getNotifications()->count();
        $metadata['deletion_timestamp'] = (new \DateTime())->format('Y-m-d H:i:s');

        $audit->setMetadata($metadata);

        // Persister
        $this->entityManager->persist($audit);
        $this->entityManager->flush();

        // Log système
        $this->logger->info('User deletion audit log created', [
            'deleted_user_id' => $deletedUser->getId(),
            'deleted_by_admin_id' => $admin->getId(),
            'audit_id' => $audit->getId(),
            'reason' => $reason,
        ]);

        return $audit;
    }

    /**
     * Vérifie si un email a déjà été supprimé
     *
     * @param string $email
     * @return bool
     */
    public function isEmailDeleted(string $email): bool
    {
        return $this->deletionAuditRepository->isEmailDeleted($email);
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
        return $this->deletionAuditRepository->getStatistics($from, $to);
    }

    /**
     * Purge les logs d'audit expirés (conformément à la politique de rétention)
     *
     * @return int Nombre de logs supprimés
     */
    public function purgeExpiredLogs(): int
    {
        $count = $this->deletionAuditRepository->purgeExpiredLogs();

        $this->logger->info('Expired deletion audit logs purged', [
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * Récupère les logs de suppression effectués par un administrateur
     *
     * @param User $admin
     * @return DeletionAudit[]
     */
    public function getLogsByAdmin(User $admin): array
    {
        return $this->deletionAuditRepository->findByAdmin($admin->getId());
    }
}
