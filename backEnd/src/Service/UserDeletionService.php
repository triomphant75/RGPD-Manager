<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de suppression d'utilisateurs conforme RGPD
 * Implémente le droit à l'effacement (Article 17 RGPD)
 *
 * Ce service garantit :
 * - La suppression complète des données personnelles (hard delete)
 * - La conservation d'un log d'audit minimal pour prouver la conformité
 * - L'anonymisation des données liées (traitements RGPD)
 * - La suppression en cascade des notifications
 */
class UserDeletionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DeletionAuditService $deletionAuditService,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Supprime un utilisateur de manière conforme RGPD
     *
     * @param User $userToDelete L'utilisateur à supprimer
     * @param User $admin L'administrateur qui effectue la suppression
     * @param string|null $reason La raison de la suppression
     * @throws \RuntimeException Si l'utilisateur tente de se supprimer lui-même
     * @return array Résultat de la suppression avec statistiques
     */
    public function deleteUserGDPRCompliant(
        User $userToDelete,
        User $admin,
        ?string $reason = null
    ): array {
        // Vérification : empêcher l'auto-suppression
        if ($userToDelete->getId() === $admin->getId()) {
            throw new \RuntimeException('Vous ne pouvez pas supprimer votre propre compte');
        }

        $this->logger->info('Starting GDPR-compliant user deletion', [
            'user_id' => $userToDelete->getId(),
            'user_email' => $userToDelete->getEmail(),
            'deleted_by' => $admin->getId(),
        ]);

        // Collecter les statistiques avant suppression
        $treatmentsCount = $userToDelete->getTreatments()->count();
        $notificationsCount = $userToDelete->getNotifications()->count();
        $userRoles = $userToDelete->getRoles();
        $userId = $userToDelete->getId();
        $userEmail = $userToDelete->getEmail();

        $metadata = [
            'treatments_count' => $treatmentsCount,
            'notifications_count' => $notificationsCount,
            'roles' => $userRoles,
        ];

        // Transaction pour garantir l'intégrité
        $this->entityManager->beginTransaction();

        try {
            // ÉTAPE 1 : Créer le log d'audit AVANT la suppression
            $this->deletionAuditService->logDeletion(
                $userToDelete,
                $admin,
                $reason,
                $metadata
            );

            // ÉTAPE 2 : Anonymiser les traitements créés par l'utilisateur
            $anonymizedIdentifier = "Utilisateur supprimé #" . $userId;

            foreach ($userToDelete->getTreatments() as $treatment) {
                $treatment->setCreatedByAnonymized($anonymizedIdentifier);
                $treatment->setCreatedBy(null);
                $this->entityManager->persist($treatment);
            }

            $this->entityManager->flush();

            // ÉTAPE 3 : Les notifications seront supprimées automatiquement
            // grâce au cascade=['remove'] défini dans l'entité User
            // Pas besoin de les supprimer manuellement

            // ÉTAPE 4 : Hard delete de l'utilisateur
            $this->entityManager->remove($userToDelete);
            $this->entityManager->flush();

            // Commit de la transaction
            $this->entityManager->commit();

            $this->logger->info('User successfully deleted (GDPR-compliant)', [
                'user_id' => $userId,
                'treatments_anonymized' => $treatmentsCount,
                'notifications_deleted' => $notificationsCount,
            ]);

            return [
                'success' => true,
                'user_id' => $userId,
                'user_email' => $userEmail,
                'treatments_anonymized' => $treatmentsCount,
                'notifications_deleted' => $notificationsCount,
                'deletion_reason' => $reason,
                'deleted_by' => $admin->getEmail(),
                'deleted_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            ];

        } catch (\Exception $e) {
            // Rollback en cas d'erreur
            $this->entityManager->rollback();

            $this->logger->error('Failed to delete user', [
                'user_id' => $userToDelete->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \RuntimeException(
                'Erreur lors de la suppression de l\'utilisateur : ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Vérifie si un utilisateur peut être supprimé
     *
     * @param User $userToDelete
     * @param User $admin
     * @return array ['can_delete' => bool, 'reason' => string|null]
     */
    public function canDeleteUser(User $userToDelete, User $admin): array
    {
        // Vérifier l'auto-suppression
        if ($userToDelete->getId() === $admin->getId()) {
            return [
                'can_delete' => false,
                'reason' => 'Vous ne pouvez pas supprimer votre propre compte',
            ];
        }

        // Vérifier si c'est le dernier admin
        if (in_array('ROLE_ADMIN', $userToDelete->getRoles(), true)) {
            // Compter les admins actifs
            $adminCount = $this->entityManager->getRepository(User::class)
                ->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->where('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_ADMIN%')
                ->getQuery()
                ->getSingleScalarResult();

            if ($adminCount <= 1) {
                return [
                    'can_delete' => false,
                    'reason' => 'Impossible de supprimer le dernier administrateur du système',
                ];
            }
        }

        return [
            'can_delete' => true,
            'reason' => null,
        ];
    }

    /**
     * Prévisualise les données qui seront affectées par la suppression
     *
     * @param User $user
     * @return array
     */
    public function previewDeletion(User $user): array
    {
        return [
            'user_id' => $user->getId(),
            'user_email' => $user->getEmail(),
            'user_roles' => $user->getRoles(),
            'treatments_count' => $user->getTreatments()->count(),
            'notifications_count' => $user->getNotifications()->count(),
            'created_at' => $user->getCreatedAt()?->format('Y-m-d H:i:s'),
            'warning' => 'Les traitements seront anonymisés, les notifications seront supprimées',
        ];
    }
}
