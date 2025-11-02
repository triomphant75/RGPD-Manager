<?php

namespace App\Service;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class AuditLogger
{
    public function __construct(
        private LoggerInterface $auditLogger,
        private Security $security,
        private RequestStack $requestStack
    ) {}
    
    /**
     * Récupère l'utilisateur connecté avec le bon type
     */
    private function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();
        
        // Vérification de type explicite
        if (!$user instanceof User) {
            return null;
        }
        
        return $user;
    }
    
    /**
     * Récupère l'IP du client
     */
    private function getClientIp(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request?->getClientIp() ?? 'unknown';
    }
    
    /**
     * Récupère le User-Agent
     */
    private function getUserAgent(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request?->headers->get('User-Agent') ?? 'unknown';
    }
    
    /**
     * Récupère les informations de l'utilisateur pour les logs
     */
    private function getUserContext(): array
    {
        $user = $this->getCurrentUser();
        
        return [
            'user_id' => $user?->getId(),
            'user_email' => $user?->getEmail(),
            'user_roles' => $user?->getRoles() ?? [],
        ];
    }
    
    /**
     * Log un accès aux données
     */
    public function logDataAccess(
        string $action,
        string $entityType,
        int|string $entityId,
        array $context = []
    ): void {
        $this->auditLogger->info('DATA_ACCESS', [
            'action' => $action, // VIEW, CREATE, UPDATE, DELETE
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            ...$this->getUserContext(),
            'ip_address' => $this->getClientIp(),
            'user_agent' => $this->getUserAgent(),
            'timestamp' => new \DateTimeImmutable(),
            'context' => $context
        ]);
    }
    
    /**
     * Log une tentative d'accès non autorisé
     */
    public function logUnauthorizedAccess(
        string $resource,
        string $reason
    ): void {
        $this->auditLogger->warning('UNAUTHORIZED_ACCESS_ATTEMPT', [
            'resource' => $resource,
            'reason' => $reason,
            ...$this->getUserContext(),
            'ip_address' => $this->getClientIp(),
            'user_agent' => $this->getUserAgent(),
            'timestamp' => new \DateTimeImmutable()
        ]);
    }
    
    /**
     * Log une modification de données sensibles
     */
    public function logSensitiveDataModification(
        string $entityType,
        int|string $entityId,
        array $changedFields
    ): void {
        $this->auditLogger->notice('SENSITIVE_DATA_MODIFICATION', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'changed_fields' => array_keys($changedFields),
            'changes' => $changedFields,
            ...$this->getUserContext(),
            'ip_address' => $this->getClientIp(),
            'timestamp' => new \DateTimeImmutable()
        ]);
    }
    
    /**
     * Log une suppression de données
     */
    public function logDataDeletion(
        string $entityType,
        int|string $entityId,
        array $metadata = []
    ): void {
        $this->auditLogger->warning('DATA_DELETION', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata,
            ...$this->getUserContext(),
            'ip_address' => $this->getClientIp(),
            'timestamp' => new \DateTimeImmutable()
        ]);
    }
    
    /**
     * Log une tentative de connexion
     */
    public function logLoginAttempt(
        string $email,
        bool $success,
        ?string $failureReason = null
    ): void {
        $logLevel = $success ? 'info' : 'warning';
        
        $this->auditLogger->log($logLevel, 'LOGIN_ATTEMPT', [
            'email' => $email,
            'success' => $success,
            'failure_reason' => $failureReason,
            'ip_address' => $this->getClientIp(),
            'user_agent' => $this->getUserAgent(),
            'timestamp' => new \DateTimeImmutable()
        ]);
    }
    
    /**
     * Log un export de données
     */
    public function logDataExport(
        string $exportType,
        array $filters = []
    ): void {
        $this->auditLogger->notice('DATA_EXPORT', [
            'export_type' => $exportType,
            'filters' => $filters,
            ...$this->getUserContext(),
            'ip_address' => $this->getClientIp(),
            'timestamp' => new \DateTimeImmutable()
        ]);
    }
    
    /**
     * Log un changement de rôle utilisateur
     */
    public function logRoleChange(
        int $targetUserId,
        string $targetUserEmail,
        array $oldRoles,
        array $newRoles
    ): void {
        $this->auditLogger->notice('USER_ROLE_CHANGE', [
            'target_user_id' => $targetUserId,
            'target_user_email' => $targetUserEmail,
            'old_roles' => $oldRoles,
            'new_roles' => $newRoles,
            ...$this->getUserContext(),
            'ip_address' => $this->getClientIp(),
            'timestamp' => new \DateTimeImmutable()
        ]);
    }
    
    /**
     * Log un changement de mot de passe
     */
    public function logPasswordChange(
        int $targetUserId,
        string $targetUserEmail,
        bool $selfChange = true
    ): void {
        $this->auditLogger->notice('PASSWORD_CHANGE', [
            'target_user_id' => $targetUserId,
            'target_user_email' => $targetUserEmail,
            'self_change' => $selfChange,
            ...$this->getUserContext(),
            'ip_address' => $this->getClientIp(),
            'timestamp' => new \DateTimeImmutable()
        ]);
    }
    
    /**
     * Log un changement d'état de traitement
     */
    public function logTreatmentStateChange(
        int $treatmentId,
        string $treatmentName,
        string $oldState,
        string $newState
    ): void {
        $this->auditLogger->info('TREATMENT_STATE_CHANGE', [
            'treatment_id' => $treatmentId,
            'treatment_name' => $treatmentName,
            'old_state' => $oldState,
            'new_state' => $newState,
            ...$this->getUserContext(),
            'ip_address' => $this->getClientIp(),
            'timestamp' => new \DateTimeImmutable()
        ]);
    }
    
    /**
     * Log une validation/refus de traitement par le DPO
     */
    public function logTreatmentValidation(
        int $treatmentId,
        string $treatmentName,
        string $action, // 'validated', 'rejected', 'modification_requested'
        ?string $comment = null
    ): void {
        $this->auditLogger->info('TREATMENT_VALIDATION_ACTION', [
            'treatment_id' => $treatmentId,
            'treatment_name' => $treatmentName,
            'action' => $action,
            'comment' => $comment,
            ...$this->getUserContext(),
            'ip_address' => $this->getClientIp(),
            'timestamp' => new \DateTimeImmutable()
        ]);
    }
    
    /**
     * Log une notification envoyée
     */
    public function logNotificationSent(
        int $recipientUserId,
        string $recipientEmail,
        string $notificationType,
        bool $emailSent = false
    ): void {
        $this->auditLogger->info('NOTIFICATION_SENT', [
            'recipient_user_id' => $recipientUserId,
            'recipient_email' => $recipientEmail,
            'notification_type' => $notificationType,
            'email_sent' => $emailSent,
            ...$this->getUserContext(),
            'timestamp' => new \DateTimeImmutable()
        ]);
    }
    
    /**
     * Log une erreur système critique
     */
    public function logSystemError(
        string $errorType,
        string $errorMessage,
        array $context = []
    ): void {
        $this->auditLogger->error('SYSTEM_ERROR', [
            'error_type' => $errorType,
            'error_message' => $errorMessage,
            'context' => $context,
            ...$this->getUserContext(),
            'ip_address' => $this->getClientIp(),
            'timestamp' => new \DateTimeImmutable()
        ]);
    }
    
    /**
     * Log une tentative de violation de données
     */
    public function logSecurityIncident(
        string $incidentType,
        string $description,
        string $severity = 'high'
    ): void {
        $this->auditLogger->critical('SECURITY_INCIDENT', [
            'incident_type' => $incidentType,
            'description' => $description,
            'severity' => $severity,
            ...$this->getUserContext(),
            'ip_address' => $this->getClientIp(),
            'user_agent' => $this->getUserAgent(),
            'timestamp' => new \DateTimeImmutable()
        ]);
    }
}