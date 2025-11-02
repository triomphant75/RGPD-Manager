<?php
// src/Command/TestNotificationCommand.php

namespace App\Command;

use App\Repository\TreatmentRepository;
use App\Service\NotificationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-notification',
    description: 'Teste la création de notifications',
)]
class TestNotificationCommand extends Command
{
    public function __construct(
        private TreatmentRepository $treatmentRepository,
        private NotificationService $notificationService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test du NotificationService');

        // Récupérer le traitement ID 13
        $treatment = $this->treatmentRepository->find(13);
        
        if (!$treatment) {
            $io->error('Traitement ID 13 non trouvé');
            return Command::FAILURE;
        }

        $io->info('Traitement trouvé : ' . $treatment->getNomTraitement());
        $io->info('État : ' . $treatment->getEtatTraitement());

        try {
            $io->section('Appel de notifyTreatmentSubmitted...');
            $this->notificationService->notifyTreatmentSubmitted($treatment);
            $io->success('Notification créée avec succès !');
        } catch (\Exception $e) {
            $io->error('Erreur : ' . $e->getMessage());
            $io->error('Fichier : ' . $e->getFile() . ' ligne ' . $e->getLine());
            $io->text('Trace : ' . $e->getTraceAsString());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}