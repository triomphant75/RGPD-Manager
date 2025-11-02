<?php

namespace App\Service;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class TreatmentValidator
{
    private const VALID_STATES = ['Brouillon', 'En validation', 'Validé', 'A modifier', 'Archivé', 'Refusé'];
    private const MAX_STRING_LENGTH = 500;
    private const MAX_TEXT_LENGTH = 5000;
    private const MAX_ARRAY_ITEMS = 100;

    public function __construct(private ValidatorInterface $validator) {}

    /**
     * Valide les données de création/mise à jour d'un traitement
     */
    public function validateTreatmentData(array $data, bool $isUpdate = false): array
    {
        $errors = [];

        // Champs obligatoires pour la création (brouillon)
        // Les brouillons peuvent être vides - aucun champ n'est obligatoire
        // Les validations strictes se font lors de la soumission au DPO
        if (!$isUpdate) {
            // Pas de champs obligatoires pour un brouillon
            // Cela permet aux utilisateurs de sauvegarder progressivement
        }

        // Validation des champs texte
        $stringFields = [
            'responsableTraitement', 'adressePostale', 'referentRGPD', 'service',
            'nomTraitement', 'numeroReference', 'finalite', 'baseJuridique',
            'referentOperationnel', 'outilInformatique', 'administrateurLogiciel',
            'hebergement', 'dureeBaseActive', 'mecanismePurge', 'droitsPersonnes',
            'sousTraitant', 'texteReglementaire', 'documentMention'
        ];

        // Fields that are nullable in the database
        // Pour les brouillons, tous les champs texte sont optionnels
        $nullableFields = [
            'numeroReference', 'sousTraitant', 'texteReglementaire', 'documentMention', 
            'dureeBaseIntermediaire', 'responsableTraitement', 'adressePostale', 'referentRGPD', 
            'service', 'nomTraitement', 'finalite', 'baseJuridique', 'referentOperationnel', 
            'outilInformatique', 'administrateurLogiciel', 'hebergement', 'dureeBaseActive', 
            'mecanismePurge', 'droitsPersonnes'
        ];

        foreach ($stringFields as $field) {
            if (isset($data[$field]) && $data[$field] !== null && $data[$field] !== '') {
                $isNullable = in_array($field, $nullableFields);
                $errors = array_merge($errors, $this->validateString($field, $data[$field], self::MAX_STRING_LENGTH, $isNullable));
            }
        }

        // Validation du téléphone
        if (isset($data['telephone'])) {
            $errors = array_merge($errors, $this->validatePhone($data['telephone']));
        }

        // Validation de la date
        if (isset($data['derniereMiseAJour'])) {
            $errors = array_merge($errors, $this->validateDate('derniereMiseAJour', $data['derniereMiseAJour']));
        }

        // Validation des tableaux
        $arrayFields = [
            'categoriePersonnes', 'sourceDonnees', 'donneesPersonnelles',
            'donneesHautementPersonnelles', 'destinatairesInternes', 'destinatairesExternes',
            'securitePhysique', 'controleAccesLogique'
        ];

        // Champs de tableau obligatoires seulement pour la soumission (pas pour le brouillon)
        $requiredArrayFields = ['categoriePersonnes', 'donneesPersonnelles'];

        foreach ($arrayFields as $field) {
            if (isset($data[$field])) {
                $errors = array_merge($errors, $this->validateArray($field, $data[$field], !$isUpdate && in_array($field, $requiredArrayFields)));
            }
        }

        // Validation des booléens
        $booleanFields = [
            'personnesVulnerables', 'donneesPapier', 'transfertHorsUE',
            'tracabilite', 'sauvegardesDonnees', 'chiffrementAnonymisation', 'archivage'
        ];

        foreach ($booleanFields as $field) {
            if (isset($data[$field])) {
                $errors = array_merge($errors, $this->validateBoolean($field, $data[$field]));
            }
        }

        // Validation de l'état du traitement
        if (isset($data['etatTraitement'])) {
            $errors = array_merge($errors, $this->validateState($data['etatTraitement']));
        }

        // Validation du volume de personnes
        if (isset($data['volumePersonnes'])) {
            $errors = array_merge($errors, $this->validateVolumePersonnes($data['volumePersonnes']));
        }

        return $errors;
    }

    /**
     * Valide les données pour la demande de modification
     */
    public function validateModificationRequest(array $data): array
    {
        $errors = [];

        if (!isset($data['comment']) || $data['comment'] === '') {
            $errors['comment'] = 'Le commentaire est obligatoire';
        } elseif (is_string($data['comment'])) {
            if (strlen(trim($data['comment'])) === 0) {
                $errors['comment'] = 'Le commentaire ne peut pas être vide';
            } elseif (strlen($data['comment']) > self::MAX_TEXT_LENGTH) {
                $errors['comment'] = "Le commentaire ne peut pas dépasser " . self::MAX_TEXT_LENGTH . " caractères";
            }
        } else {
            $errors['comment'] = 'Le commentaire doit être une chaîne de caractères';
        }

        return $errors;
    }

    /**
     * Valide une chaîne de caractères
     */
    private function validateString(string $fieldName, mixed $value, int $maxLength = self::MAX_STRING_LENGTH, bool $isNullable = false): array
    {
        $errors = [];

        if (!is_string($value)) {
            $errors[$fieldName] = "$fieldName doit être une chaîne de caractères";
            return $errors;
        }

        $trimmed = trim($value);
        if (empty($trimmed)) {
            if (!$isNullable) {
                $errors[$fieldName] = "$fieldName ne peut pas être vide";
            }
            return $errors;
        }

        if (strlen($value) > $maxLength) {
            $errors[$fieldName] = "$fieldName ne peut pas dépasser $maxLength caractères";
        }

        // Vérifier les caractères de contrôle
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)) {
            $errors[$fieldName] = "$fieldName contient des caractères invalides";
        }

        return $errors;
    }

    /**
     * Valide un numéro de téléphone
     */
    private function validatePhone(string $phone): array
    {
        $errors = [];

        if (!is_string($phone)) {
            $errors['telephone'] = 'Le téléphone doit être une chaîne de caractères';
            return $errors;
        }

        // Format: +33 ou 0, suivi de 9 chiffres
        if (!preg_match('/^(?:\+33|0)[1-9](?:[0-9]{8})$/', str_replace([' ', '.', '-'], '', $phone))) {
            $errors['telephone'] = 'Le numéro de téléphone n\'est pas valide';
        }

        return $errors;
    }

    /**
     * Valide une date
     */
    private function validateDate(string $fieldName, mixed $value): array
    {
        $errors = [];

        if (!is_string($value)) {
            $errors[$fieldName] = "$fieldName doit être une chaîne de caractères au format ISO 8601";
            return $errors;
        }

        try {
            $date = new \DateTime($value);
            
            // Vérifier que la date n'est pas dans le futur
            if ($date > new \DateTime()) {
                $errors[$fieldName] = "$fieldName ne peut pas être dans le futur";
            }
        } catch (\Exception $e) {
            $errors[$fieldName] = "$fieldName n'est pas une date valide (format: YYYY-MM-DD)";
        }

        return $errors;
    }

    /**
     * Valide un tableau
     */
    private function validateArray(string $fieldName, mixed $value, bool $isRequired = true): array
    {
        $errors = [];

        if (!is_array($value)) {
            $errors[$fieldName] = "$fieldName doit être un tableau";
            return $errors;
        }

        if (empty($value)) {
            if ($isRequired) {
                $errors[$fieldName] = "$fieldName ne peut pas être vide";
            }
            return $errors;
        }

        if (count($value) > self::MAX_ARRAY_ITEMS) {
            $errors[$fieldName] = "$fieldName ne peut pas contenir plus de " . self::MAX_ARRAY_ITEMS . " éléments";
            return $errors;
        }

        // Vérifier que tous les éléments sont des chaînes non vides
        foreach ($value as $index => $item) {
            if (!is_string($item)) {
                $errors[$fieldName] = "$fieldName[$index] doit être une chaîne de caractères";
                break;
            }
            if (empty(trim($item))) {
                $errors[$fieldName] = "$fieldName[$index] ne peut pas être vide";
                break;
            }
            if (strlen($item) > self::MAX_STRING_LENGTH) {
                $errors[$fieldName] = "$fieldName[$index] dépasse la longueur maximale";
                break;
            }
        }

        return $errors;
    }

    /**
     * Valide un booléen
     */
    private function validateBoolean(string $fieldName, mixed $value): array
    {
        $errors = [];

        if (!is_bool($value)) {
            // Accepter les chaînes "true"/"false" et les entiers 0/1
            if (!in_array($value, [0, 1, 'true', 'false', true, false], true)) {
                $errors[$fieldName] = "$fieldName doit être un booléen";
            }
        }

        return $errors;
    }

    /**
     * Valide l'état du traitement
     */
    private function validateState(mixed $value): array
    {
        $errors = [];

        if (!is_string($value)) {
            $errors['etatTraitement'] = 'L\'état du traitement doit être une chaîne de caractères';
            return $errors;
        }

        if (!in_array($value, self::VALID_STATES, true)) {
            $errors['etatTraitement'] = 'L\'état du traitement n\'est pas valide. États autorisés: ' . implode(', ', self::VALID_STATES);
        }

        return $errors;
    }

    /**
     * Valide un entier
     */
    private function validateInteger(string $fieldName, mixed $value, int $min = 0): array
    {
        $errors = [];

        // Allow null/empty for optional fields like volumePersonnes
        if ($value === null || $value === '') {
            return $errors;
        }

        if (!is_int($value) && !is_numeric($value)) {
            $errors[$fieldName] = "$fieldName doit être un nombre entier";
            return $errors;
        }

        $intValue = (int)$value;
        if ($intValue < $min) {
            $errors[$fieldName] = "$fieldName doit être supérieur ou égal à $min";
        }

        return $errors;
    }

    /**
     * Valide le volume de personnes (accepte les plages comme "<50", "50-100", etc.)
     */
    private function validateVolumePersonnes(mixed $value): array
    {
        $errors = [];

        // Allow null/empty for optional fields
        if ($value === null || $value === '') {
            return $errors;
        }

        if (!is_string($value)) {
            $errors['volumePersonnes'] = 'volumePersonnes doit être une chaîne de caractères';
            return $errors;
        }

        // Valeurs acceptées pour les plages de volume
        $validVolumes = ['<50', '50-100', '100-1000', '>1000'];

        if (!in_array($value, $validVolumes, true)) {
            $errors['volumePersonnes'] = 'volumePersonnes doit être l\'une des valeurs suivantes: ' . implode(', ', $validVolumes);
        }

        return $errors;
    }

    /**
     * Retourne les états valides
     */
    public static function getValidStates(): array
    {
        return self::VALID_STATES;
    }
}
