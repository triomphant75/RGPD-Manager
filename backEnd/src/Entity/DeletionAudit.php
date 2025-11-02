<?php

namespace App\Entity;

use App\Repository\DeletionAuditRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité pour l'audit des suppressions d'utilisateurs (conformité RGPD Article 17)
 *
 * Cette table conserve une trace minimale des suppressions d'utilisateurs
 * pour prouver la conformité RGPD tout en respectant le droit à l'effacement.
 */
#[ORM\Entity(repositoryClass: DeletionAuditRepository::class)]
#[ORM\Table(name: 'deletion_audit')]
class DeletionAudit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Hash SHA256 de l'email de l'utilisateur supprimé
     * Permet d'éviter les recréations tout en ne stockant pas l'email
     */
    #[ORM\Column(length: 64)]
    private ?string $emailHash = null;

    /**
     * Identifiant anonymisé de l'utilisateur (ex: "USER_12345")
     */
    #[ORM\Column(length: 50)]
    private ?string $userIdAnonymized = null;

    /**
     * Date et heure de la suppression
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $deletionDate = null;

    /**
     * ID de l'administrateur qui a effectué la suppression
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $deletedByAdmin = null;

    /**
     * Raison de la suppression (ex: "Demande utilisateur", "Compte inactif", etc.)
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $deletionReason = null;

    /**
     * Adresse IP de l'administrateur qui a effectué la suppression
     */
    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress = null;

    /**
     * Date jusqu'à laquelle ce log doit être conservé (RGPD: max 5 ans recommandé)
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $retentionUntil = null;

    /**
     * Métadonnées supplémentaires en JSON (ex: nombre de traitements, rôle, etc.)
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    public function __construct()
    {
        $this->deletionDate = new \DateTime();
        // Rétention par défaut: 5 ans (recommandation RGPD)
        $this->retentionUntil = (new \DateTime())->modify('+5 years');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmailHash(): ?string
    {
        return $this->emailHash;
    }

    public function setEmailHash(string $emailHash): static
    {
        $this->emailHash = $emailHash;
        return $this;
    }

    public function getUserIdAnonymized(): ?string
    {
        return $this->userIdAnonymized;
    }

    public function setUserIdAnonymized(string $userIdAnonymized): static
    {
        $this->userIdAnonymized = $userIdAnonymized;
        return $this;
    }

    public function getDeletionDate(): ?\DateTimeInterface
    {
        return $this->deletionDate;
    }

    public function setDeletionDate(\DateTimeInterface $deletionDate): static
    {
        $this->deletionDate = $deletionDate;
        return $this;
    }

    public function getDeletedByAdmin(): ?User
    {
        return $this->deletedByAdmin;
    }

    public function setDeletedByAdmin(?User $deletedByAdmin): static
    {
        $this->deletedByAdmin = $deletedByAdmin;
        return $this;
    }

    public function getDeletionReason(): ?string
    {
        return $this->deletionReason;
    }

    public function setDeletionReason(?string $deletionReason): static
    {
        $this->deletionReason = $deletionReason;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getRetentionUntil(): ?\DateTimeInterface
    {
        return $this->retentionUntil;
    }

    public function setRetentionUntil(?\DateTimeInterface $retentionUntil): static
    {
        $this->retentionUntil = $retentionUntil;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }
}
