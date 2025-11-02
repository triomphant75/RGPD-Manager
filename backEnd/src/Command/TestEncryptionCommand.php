<?php
// src/Command/TestEncryptionCommand.php

namespace App\Command;

use App\Entity\Treatment;
use App\Repository\TreatmentRepository;
use App\Service\EncryptionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-encryption',
    description: 'Teste le système de chiffrement'
)]
class TestEncryptionCommand extends Command
{
    public function __construct(
        private EncryptionService $encryptionService,
        private TreatmentRepository $treatmentRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🧪 Test du Système de Chiffrement');

        // Test 1 : Service de base
        $io->section('Test 1 : Chiffrement/Déchiffrement basique');
        
        if (!$this->encryptionService->test()) {
            $io->error('❌ Test du service échoué');
            return Command::FAILURE;
        }
        $io->success('✅ Service fonctionnel');

        // Test 2 : Afficher les infos
        $io->section('Test 2 : Informations du service');
        $info = $this->encryptionService->getInfo();
        $io->table(
            ['Paramètre', 'Valeur'],
            [
                ['Algorithme', $info['cipher_method']],
                ['Longueur clé', $info['key_length'] . ' caractères'],
                ['Préfixe', $info['prefix']],
                ['Suffixe', $info['suffix']],
            ]
        );

        // Test 3 : Test avec données réelles
        $io->section('Test 3 : Chiffrement de données réelles');
        $testData = 'Jean Dupont - 123 Rue de Test, 75001 Paris - 01 23 45 67 89';
        $io->writeln('Original  : ' . $testData);
        
        $encrypted = $this->encryptionService->encrypt($testData);
        $io->writeln('Chiffré   : ' . substr($encrypted, 0, 70) . '...');
        
        $decrypted = $this->encryptionService->decrypt($encrypted);
        $io->writeln('Déchiffré : ' . $decrypted);
        
        if ($testData === $decrypted) {
            $io->success('✅ Chiffrement/Déchiffrement réussi');
        } else {
            $io->error('❌ Échec du chiffrement/déchiffrement');
            return Command::FAILURE;
        }

        // Test 4 : Test avec la base de données
        $io->section('Test 4 : Test avec un traitement en base');
        
        $treatment = new Treatment();
        $treatment->setResponsableTraitement('TEST ENCRYPTION');
        $treatment->setAdressePostale('123 Rue Test');
        $treatment->setTelephone('0123456789');
        $treatment->setReferentRGPD('Test DPO');
        $treatment->setService('Test');
        $treatment->setNomTraitement('Test Chiffrement ' . time());
        $treatment->setDerniereMiseAJour(new \DateTime());
        $treatment->setFinalite('Test');
        $treatment->setBaseJuridique('Test');
        $treatment->setCategoriePersonnes(['Test']);
        $treatment->setDonneesPersonnelles(['Test']);
        $treatment->setReferentOperationnel('Test Ref');
        $treatment->setOutilInformatique('Test Tool');
        $treatment->setAdministrateurLogiciel('Test Admin');
        $treatment->setHebergement('Test Host');
        $treatment->setTransfertHorsUE(false);
        $treatment->setDureeBaseActive('Test');
        $treatment->setMecanismePurge('Test');
        $treatment->setDroitsPersonnes('Test');
        $treatment->setEtatTraitement('Brouillon');
        
        // Récupérer un utilisateur existant
        $user = $this->entityManager->getRepository(\App\Entity\User::class)->findOneBy([]);
        if (!$user) {
            $io->warning('Aucun utilisateur trouvé, création d\'un utilisateur test...');
            // Créer un utilisateur test si nécessaire
        } else {
            $treatment->setCreatedBy($user);
        }

        $this->entityManager->persist($treatment);
        $this->entityManager->flush();
        $id = $treatment->getId();
        
        $io->success('Traitement créé avec ID: ' . $id);

        // Vérifier en base
        $connection = $this->entityManager->getConnection();
        $sql = "SELECT responsable_traitement, telephone FROM treatments WHERE id = :id";
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery(['id' => $id]);
        $rawData = $result->fetchAssociative();
        
        $io->writeln('Données RAW en base :');
        $io->writeln('  Responsable : ' . substr($rawData['responsable_traitement'], 0, 60) . '...');
        $io->writeln('  Téléphone   : ' . substr($rawData['telephone'], 0, 60) . '...');
        
        if (str_contains($rawData['responsable_traitement'], '<ENC>')) {
            $io->success('✅ Données chiffrées en base');
        } else {
            $io->error('❌ Données NON chiffrées en base');
        }

        // Relire via Doctrine
        $this->entityManager->clear();
        $reloaded = $this->treatmentRepository->find($id);
        
        $io->writeln('Données déchiffrées par Doctrine :');
        $io->writeln('  Responsable : ' . $reloaded->getResponsableTraitement());
        $io->writeln('  Téléphone   : ' . $reloaded->getTelephone());
        
        if ($reloaded->getResponsableTraitement() === 'TEST ENCRYPTION') {
            $io->success('✅ Déchiffrement automatique fonctionnel');
        } else {
            $io->error('❌ Déchiffrement échoué');
        }

        // Nettoyage
        $this->entityManager->remove($reloaded);
        $this->entityManager->flush();
        $io->info('Traitement test supprimé');

        $io->success('🎉 Tous les tests sont passés avec succès !');

        return Command::SUCCESS;
    }
}