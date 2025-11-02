<?php
// src/Command/GenerateEncryptionKeyCommand.php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-encryption-key',
    description: 'Génère une clé de chiffrement sécurisée'
)]
class GenerateEncryptionKeyCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🔑 Génération de Clé de Chiffrement');

        try {
            // Générer une clé aléatoire de 64 caractères (hex)
            $key = bin2hex(random_bytes(32));

            $io->success('Clé générée avec succès !');
            $io->section('📋 Copiez cette clé dans votre fichier .env :');
            $io->writeln('');
            $io->writeln('ENCRYPTION_KEY=' . $key);
            $io->writeln('');
            
            $io->warning([
                '⚠️  IMPORTANT :',
                '1. Copiez cette clé dans .env.local (dev) et .env.prod (production)',
                '2. Utilisez des clés DIFFÉRENTES pour chaque environnement',
                '3. Ne commitez JAMAIS ce fichier dans Git',
                '4. Sauvegardez cette clé de manière sécurisée (coffre-fort, gestionnaire de secrets)',
                '5. Si vous perdez cette clé, les données chiffrées seront IRRÉCUPÉRABLES',
                '6. Changez la clé tous les 6-12 mois (avec rotation des données)'
            ]);

            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('Erreur lors de la génération : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}