<?php
// src/Service/EncryptionService.php

namespace App\Service;

use RuntimeException;

/**
 * Service de chiffrement AES-256-CBC pour les données personnelles
 * Conforme aux recommandations ANSSI et RGPD
 */
class EncryptionService
{
    private const CIPHER_METHOD = 'aes-256-cbc';
    private const PREFIX = '<ENC>';
    private const SUFFIX = '</ENC>';
    private const MIN_KEY_LENGTH = 32;

    private string $encryptionKey;

    public function __construct(string $encryptionKey)
    {
        if (strlen($encryptionKey) < self::MIN_KEY_LENGTH) {
            throw new RuntimeException(
                sprintf('Encryption key must be at least %d characters', self::MIN_KEY_LENGTH)
            );
        }
        
        // Utiliser les 32 premiers caractères comme clé
        $this->encryptionKey = substr($encryptionKey, 0, 32);
    }

    /**
     * Chiffre une chaîne de caractères
     * 
     * @param string|null $data Données à chiffrer
     * @return string|null Données chiffrées ou null si données vides
     * @throws RuntimeException Si le chiffrement échoue
     */
    public function encrypt(?string $data): ?string
    {
        if ($data === null || $data === '') {
            return $data;
        }

        // Éviter le double chiffrement
        if ($this->isEncrypted($data)) {
            return $data;
        }

        try {
            $ivLength = openssl_cipher_iv_length(self::CIPHER_METHOD);
            if ($ivLength === false) {
                throw new RuntimeException('Unable to determine IV length');
            }

            $iv = openssl_random_pseudo_bytes($ivLength, $strong);
            if (!$strong) {
                throw new RuntimeException('IV generation failed');
            }
            
            $encrypted = openssl_encrypt(
                $data,
                self::CIPHER_METHOD,
                $this->encryptionKey,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($encrypted === false) {
                throw new RuntimeException('Encryption failed: ' . openssl_error_string());
            }

            // Combiner IV + données chiffrées, encoder en base64, ajouter préfixe/suffixe
            $combined = base64_encode($iv . $encrypted);
            return self::PREFIX . $combined . self::SUFFIX;
            
        } catch (\Throwable $e) {
            throw new RuntimeException('Encryption error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Déchiffre une chaîne de caractères
     * 
     * @param string|null $data Données à déchiffrer
     * @return string|null Données déchiffrées ou null si données vides
     * @throws RuntimeException Si le déchiffrement échoue
     */
    public function decrypt(?string $data): ?string
    {
        if ($data === null || $data === '') {
            return $data;
        }

        // Si pas chiffré, retourner tel quel
        if (!$this->isEncrypted($data)) {
            return $data;
        }

        try {
            // Retirer préfixe/suffixe
            $data = str_replace([self::PREFIX, self::SUFFIX], '', $data);
            $decoded = base64_decode($data, true);

            if ($decoded === false) {
                throw new RuntimeException('Invalid base64 data');
            }

            $ivLength = openssl_cipher_iv_length(self::CIPHER_METHOD);
            if ($ivLength === false) {
                throw new RuntimeException('Unable to determine IV length');
            }
            
            if (strlen($decoded) < $ivLength) {
                throw new RuntimeException('Invalid encrypted data length');
            }
            
            $iv = substr($decoded, 0, $ivLength);
            $encrypted = substr($decoded, $ivLength);

            $decrypted = openssl_decrypt(
                $encrypted,
                self::CIPHER_METHOD,
                $this->encryptionKey,
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($decrypted === false) {
                throw new RuntimeException('Decryption failed: ' . openssl_error_string());
            }

            return $decrypted;
            
        } catch (\Throwable $e) {
            throw new RuntimeException('Decryption error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Vérifie si une donnée est déjà chiffrée
     * 
     * @param string|null $data Données à vérifier
     * @return bool True si les données sont chiffrées
     */
    private function isEncrypted(?string $data): bool
    {
        if ($data === null || $data === '') {
            return false;
        }

        return str_starts_with($data, self::PREFIX) && str_ends_with($data, self::SUFFIX);
    }

    /**
     * Teste le bon fonctionnement du service
     * 
     * @return bool True si le test réussit
     */
    public function test(): bool
    {
        try {
            $testData = 'Test Encryption ' . time();
            $encrypted = $this->encrypt($testData);
            $decrypted = $this->decrypt($encrypted);
            
            return $testData === $decrypted;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Retourne des informations sur le service
     * 
     * @return array Informations du service
     */
    public function getInfo(): array
    {
        return [
            'cipher_method' => self::CIPHER_METHOD,
            'key_length' => strlen($this->encryptionKey),
            'prefix' => self::PREFIX,
            'suffix' => self::SUFFIX,
        ];
    }
}