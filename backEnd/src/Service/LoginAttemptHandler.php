<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service de gestion des tentatives de connexion échouées
 * Implémente un système de rate limiting pour prévenir les attaques par force brute
 */
class LoginAttemptHandler
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_DURATION = 900; // 15 minutes en secondes
    private const ATTEMPT_WINDOW = 300; // 5 minutes en secondes

    private array $attempts = [];
    private array $lockouts = [];

    public function __construct(
        private RequestStack $requestStack
    ) {}

    /**
     * Enregistre une tentative de connexion échouée
     */
    public function recordAttempt(string $identifier): void
    {
        $key = $this->getKey($identifier);
        $now = time();

        if (!isset($this->attempts[$key])) {
            $this->attempts[$key] = [];
        }

        // Supprimer les tentatives trop anciennes (hors de la fenêtre de temps)
        $this->attempts[$key] = array_filter(
            $this->attempts[$key],
            fn($timestamp) => ($now - $timestamp) < self::ATTEMPT_WINDOW
        );

        // Ajouter la nouvelle tentative
        $this->attempts[$key][] = $now;

        // Si trop de tentatives, verrouiller le compte
        if (count($this->attempts[$key]) >= self::MAX_ATTEMPTS) {
            $this->lockouts[$key] = $now + self::LOCKOUT_DURATION;
        }
    }

    /**
     * Vérifie si un identifiant est bloqué
     */
    public function isBlocked(string $identifier): bool
    {
        $key = $this->getKey($identifier);
        $now = time();

        // Vérifier si le compte est verrouillé
        if (isset($this->lockouts[$key])) {
            if ($this->lockouts[$key] > $now) {
                return true;
            }
            // Le verrouillage a expiré, le supprimer
            unset($this->lockouts[$key]);
            unset($this->attempts[$key]);
        }

        return false;
    }

    /**
     * Réinitialise les tentatives après une connexion réussie
     */
    public function resetAttempts(string $identifier): void
    {
        $key = $this->getKey($identifier);
        unset($this->attempts[$key]);
        unset($this->lockouts[$key]);
    }

    /**
     * Retourne le nombre de tentatives restantes
     */
    public function getRemainingAttempts(string $identifier): int
    {
        $key = $this->getKey($identifier);
        $now = time();

        if (!isset($this->attempts[$key])) {
            return self::MAX_ATTEMPTS;
        }

        // Supprimer les tentatives trop anciennes
        $this->attempts[$key] = array_filter(
            $this->attempts[$key],
            fn($timestamp) => ($now - $timestamp) < self::ATTEMPT_WINDOW
        );

        $attemptCount = count($this->attempts[$key]);
        return max(0, self::MAX_ATTEMPTS - $attemptCount);
    }

    /**
     * Retourne le temps restant de verrouillage en secondes
     */
    public function getLockoutTimeRemaining(string $identifier): int
    {
        $key = $this->getKey($identifier);
        $now = time();

        if (!isset($this->lockouts[$key])) {
            return 0;
        }

        $remaining = $this->lockouts[$key] - $now;
        return max(0, $remaining);
    }

    /**
     * Génère une clé unique basée sur l'identifiant et l'IP
     */
    private function getKey(string $identifier): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $ip = $request ? $request->getClientIp() : 'unknown';

        return hash('sha256', $identifier . '|' . $ip);
    }
}
