<?php

namespace App\Service;

use App\Entity\DataBreachIncident;
use Doctrine\ORM\EntityManagerInterface;

class DataBreachService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ?AuditLogger $auditLogger = null,
    ) {}

    /**
     * Déclare un incident et évalue le risque selon des heuristiques simples.
     * @param array $payload
     */
    public function reportIncident(array $payload): DataBreachIncident
    {
        $incident = new DataBreachIncident();

        // Validation basique et affectation
        $description = trim((string)($payload['description'] ?? ''));
        if ($description === '') {
            throw new \InvalidArgumentException('description is required');
        }
        $incident->setDescription($description);

        $detectedAt = $payload['detectedAt'] ?? null;
        if ($detectedAt) {
            $incident->setDetectedAt($this->toImmutableDate($detectedAt));
        }

        $incident->setSource(isset($payload['source']) ? (string)$payload['source'] : null);

        $personalDataInvolved = (bool)($payload['personalDataInvolved'] ?? true);
        $incident->setPersonalDataInvolved($personalDataInvolved);

        $types = $payload['personalDataTypes'] ?? null;
        if (is_array($types)) { $incident->setPersonalDataTypes($types); }

        $count = (int)($payload['affectedSubjectsCount'] ?? 0);
        $incident->setAffectedSubjectsCount($count);

        // Évaluation risque + sévérité
        [$risk, $severity, $notify] = $this->assessRisk([
            'types' => $types,
            'count' => $count,
            'encrypted' => (bool)($payload['encrypted'] ?? false),
            'publiclyExposed' => (bool)($payload['publiclyExposed'] ?? false),
        ]);

        $incident->setRiskAssessment($risk);
        $incident->setSeverity($severity);
        $incident->setNotificationRequired($notify);
        $incident->setStatus('under_review');

        $this->em->persist($incident);
        $this->em->flush();

        $this->audit('DATA_BREACH_REPORTED', sprintf('Breach reported id=%d risk=%s severity=%s notify=%s', $incident->getId(), $risk, $severity, $notify ? 'yes' : 'no'), $severity);

        return $incident;
    }

    /**
     * Heuristiques d'évaluation du risque et décision de notification.
     * Retourne [risk, severity, notificationRequired]
     */
    public function assessRisk(array $context): array
    {
        $typesRaw = $context['types'] ?? [];
        $types = is_array($typesRaw) ? array_map('strtolower', $typesRaw) : [];
        $count = (int)($context['count'] ?? 0);
        $encrypted = (bool)($context['encrypted'] ?? false);
        $public = (bool)($context['publiclyExposed'] ?? false);

        $score = 0;

        // Types sensibles
        $sensitive = ['santé', 'sante', 'health', 'financiere', 'financières', 'financial', 'credential', 'credentials', 'password', 'mineur', 'minor'];
        foreach ($types as $t) {
            foreach ($sensitive as $s) {
                if (str_contains($t, $s)) { $score += 2; break; }
            }
        }

        // Volume
        if ($count >= 5000) $score += 2; elseif ($count >= 1000) $score += 1;

        // Exposition publique
        if ($public) $score += 3;

        // Chiffrement
        if ($encrypted) $score -= 2;

        // Normalisation des bornes
        if ($score < 0) $score = 0;

        // Mapping du score
        $risk = 'none';
        $severity = 'low';
        if ($score >= 6) { $risk = 'high'; $severity = 'critical'; }
        elseif ($score >= 4) { $risk = 'medium'; $severity = 'high'; }
        elseif ($score >= 2) { $risk = 'low'; $severity = 'medium'; }
        else { $risk = 'none'; $severity = 'low'; }

        // Notification: si medium/high et non protégé/ public
        $notify = ($risk === 'medium' || $risk === 'high');

        return [$risk, $severity, $notify];
    }

    public function markAuthorityNotified(DataBreachIncident $incident, ?string $reference = null): void
    {
        $incident->setAuthorityNotifiedAt(new \DateTimeImmutable());
        $incident->setAuthorityReference($reference);
        $incident->setStatus('notified');
        $this->em->flush();
        $this->audit('AUTHORITY_NOTIFIED', sprintf('Authority notified for incident id=%d ref=%s', $incident->getId(), $reference ?? 'N/A'), 'medium');
    }

    public function markSubjectsNotified(DataBreachIncident $incident): void
    {
        $incident->setSubjectsNotifiedAt(new \DateTimeImmutable());
        $this->em->flush();
        $this->audit('SUBJECTS_NOTIFIED', sprintf('Subjects notified for incident id=%d', $incident->getId()), 'medium');
    }

    private function toImmutableDate(string|\DateTimeInterface $d): \DateTimeImmutable
    {
        if ($d instanceof \DateTimeImmutable) return $d;
        if ($d instanceof \DateTime) return \DateTimeImmutable::createFromMutable($d);
        return new \DateTimeImmutable((string)$d);
    }

    private function audit(string $incidentType, string $description, string $severity = 'high'): void
    {
        if ($this->auditLogger) {
            try { $this->auditLogger->logSecurityIncident($incidentType, $description, $severity); } catch (\Throwable) {}
        }
    }
}
