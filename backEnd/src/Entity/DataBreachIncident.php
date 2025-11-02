<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'data_breach_incident')]
class DataBreachIncident
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $detectedAt;

    #[ORM\Column(type: 'string', length: 20)]
    private string $severity = 'low';

    #[ORM\Column(type: 'text')]
    private string $description;

    #[ORM\Column(type: 'boolean')]
    private bool $personalDataInvolved = true;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $personalDataTypes = null; // e.g., ["health", "financial", "credentials"]

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $affectedSubjectsCount = 0;

    #[ORM\Column(type: 'string', length: 20)]
    private string $riskAssessment = 'low'; // none|low|medium|high

    #[ORM\Column(type: 'boolean')]
    private bool $notificationRequired = false;

    #[ORM\Column(type: 'boolean')]
    private bool $dpoReviewed = false;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'open'; // open|under_review|notified|closed

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $authorityNotifiedAt = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $authorityReference = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $subjectsNotifiedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $containmentActions = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $remediationActions = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $source = null; // e.g., IDS, log, report

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->detectedAt = $now;
    }

    public function getId(): ?int { return $this->id; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getDetectedAt(): \DateTimeImmutable { return $this->detectedAt; }
    public function setDetectedAt(\DateTimeImmutable $d): self { $this->detectedAt = $d; return $this; }

    public function getSeverity(): string { return $this->severity; }
    public function setSeverity(string $s): self { $this->severity = $s; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $d): self { $this->description = $d; return $this; }

    public function isPersonalDataInvolved(): bool { return $this->personalDataInvolved; }
    public function setPersonalDataInvolved(bool $b): self { $this->personalDataInvolved = $b; return $this; }

    public function getPersonalDataTypes(): ?array { return $this->personalDataTypes; }
    public function setPersonalDataTypes(?array $t): self { $this->personalDataTypes = $t; return $this; }

    public function getAffectedSubjectsCount(): int { return $this->affectedSubjectsCount; }
    public function setAffectedSubjectsCount(int $n): self { $this->affectedSubjectsCount = max(0, $n); return $this; }

    public function getRiskAssessment(): string { return $this->riskAssessment; }
    public function setRiskAssessment(string $r): self { $this->riskAssessment = $r; return $this; }

    public function isNotificationRequired(): bool { return $this->notificationRequired; }
    public function setNotificationRequired(bool $b): self { $this->notificationRequired = $b; return $this; }

    public function isDpoReviewed(): bool { return $this->dpoReviewed; }
    public function setDpoReviewed(bool $b): self { $this->dpoReviewed = $b; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $s): self { $this->status = $s; return $this; }

    public function getAuthorityNotifiedAt(): ?\DateTimeImmutable { return $this->authorityNotifiedAt; }
    public function setAuthorityNotifiedAt(?\DateTimeImmutable $d): self { $this->authorityNotifiedAt = $d; return $this; }

    public function getAuthorityReference(): ?string { return $this->authorityReference; }
    public function setAuthorityReference(?string $r): self { $this->authorityReference = $r; return $this; }

    public function getSubjectsNotifiedAt(): ?\DateTimeImmutable { return $this->subjectsNotifiedAt; }
    public function setSubjectsNotifiedAt(?\DateTimeImmutable $d): self { $this->subjectsNotifiedAt = $d; return $this; }

    public function getContainmentActions(): ?string { return $this->containmentActions; }
    public function setContainmentActions(?string $t): self { $this->containmentActions = $t; return $this; }

    public function getRemediationActions(): ?string { return $this->remediationActions; }
    public function setRemediationActions(?string $t): self { $this->remediationActions = $t; return $this; }

    public function getSource(): ?string { return $this->source; }
    public function setSource(?string $s): self { $this->source = $s; return $this; }
}
