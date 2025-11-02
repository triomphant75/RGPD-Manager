<?php
// src/EventListener/EncryptionListener.php

namespace App\EventListener;

use App\Entity\Treatment;
use App\Entity\User;
use App\Service\EncryptionService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsDoctrineListener(event: Events::prePersist, priority: 500)]
#[AsDoctrineListener(event: Events::preUpdate, priority: 500)]
#[AsDoctrineListener(event: Events::postLoad, priority: 500)]
class EncryptionListener
{
    private bool $enabled;

    public function __construct(
        private EncryptionService $encryptionService,
        private LoggerInterface $logger,
        string $appEnv = 'prod'
    ) {
        // Activer le chiffrement uniquement en production
        $this->enabled = ($appEnv === 'prod');
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->encryptEntity($args->getObject());
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->encryptEntity($args->getObject());
    }

    public function postLoad(PostLoadEventArgs $args): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->decryptEntity($args->getObject());
    }

    private function encryptEntity(object $entity): void
    {
        try {
            if ($entity instanceof Treatment) {
                $this->encryptTreatment($entity);
            } elseif ($entity instanceof User) {
                $this->encryptUser($entity);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Encryption error', [
                'entity' => get_class($entity),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function decryptEntity(object $entity): void
    {
        try {
            if ($entity instanceof Treatment) {
                $this->decryptTreatment($entity);
            } elseif ($entity instanceof User) {
                $this->decryptUser($entity);
            }
        } catch (\Throwable $e) {
            $this->logger->error('Decryption error', [
                'entity' => get_class($entity),
                'error' => $e->getMessage()
            ]);
            // Ne pas throw pour éviter de bloquer l'application
        }
    }

    /**
     * Chiffre les champs sensibles d'un Treatment
     */
    private function encryptTreatment(Treatment $treatment): void
    {
        $fieldsToEncrypt = [
            'responsableTraitement',
            'adressePostale',
            'telephone',
            'referentRGPD',
            'finalite',
            'referentOperationnel',
            'sousTraitant',
            'administrateurLogiciel',
        ];

        foreach ($fieldsToEncrypt as $field) {
            $this->encryptField($treatment, $field);
        }
    }

    /**
     * Déchiffre les champs sensibles d'un Treatment
     */
    private function decryptTreatment(Treatment $treatment): void
    {
        $fieldsToDecrypt = [
            'responsableTraitement',
            'adressePostale',
            'telephone',
            'referentRGPD',
            'finalite',
            'referentOperationnel',
            'sousTraitant',
            'administrateurLogiciel',
        ];

        foreach ($fieldsToDecrypt as $field) {
            $this->decryptField($treatment, $field);
        }
    }

    /**
     * Chiffre les champs sensibles d'un User
     */
    private function encryptUser(User $user): void
    {
        $this->encryptField($user, 'email');
    }

    /**
     * Déchiffre les champs sensibles d'un User
     */
    private function decryptUser(User $user): void
    {
        $this->decryptField($user, 'email');
    }

    /**
     * Chiffre un champ spécifique d'une entité
     */
    private function encryptField(object $entity, string $field): void
    {
        $getter = 'get' . ucfirst($field);
        $setter = 'set' . ucfirst($field);
        
        if (!method_exists($entity, $getter) || !method_exists($entity, $setter)) {
            return;
        }

        $value = $entity->$getter();
        if ($value !== null && $value !== '') {
            $encrypted = $this->encryptionService->encrypt($value);
            $entity->$setter($encrypted);
        }
    }

    /**
     * Déchiffre un champ spécifique d'une entité
     */
    private function decryptField(object $entity, string $field): void
    {
        $getter = 'get' . ucfirst($field);
        $setter = 'set' . ucfirst($field);
        
        if (!method_exists($entity, $getter) || !method_exists($entity, $setter)) {
            return;
        }

        $value = $entity->$getter();
        if ($value !== null && $value !== '') {
            $decrypted = $this->encryptionService->decrypt($value);
            $entity->$setter($decrypted);
        }
    }
}