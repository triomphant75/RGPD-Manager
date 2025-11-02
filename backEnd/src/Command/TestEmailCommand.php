<?php

namespace App\Command;

use App\Service\NotificationService;
use App\Repository\TreatmentRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-email',
    description: 'Test l\'envoi d\'email pour un traitement',
)]
class TestEmailCommand extends Command
{
    public function __construct(
        private NotificationService $notificationService,
        private TreatmentRepository $treatmentRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Test d\'envoi d\'email de notification');

        // Trouver un traitement
        $treatment = $this->treatmentRepository->findOneBy([], ['id' => 'DESC']);

        if (!$treatment) {
            $io->error('Aucun traitement trouvé dans la base de données');
            return Command::FAILURE;
        }

        $io->info("Traitement trouvé: {$treatment->getNomTraitement()} (ID: {$treatment->getId()})");

        try {
            $io->section('Envoi de la notification...');
            $this->notificationService->notifyTreatmentSubmitted($treatment);
            $io->success('Email envoyé avec succès !');
            $io->info('Vérifiez Mailtrap : https://mailtrap.io/inboxes');
        } catch (\Exception $e) {
            $io->error('Erreur lors de l\'envoi: ' . $e->getMessage());
            $io->writeln($e->getTraceAsString());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
