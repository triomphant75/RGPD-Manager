<?php

namespace App\Command;

use App\Service\DeletionAuditService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Commande pour purger les logs d'audit de suppression expirés
 *
 * Cette commande doit être exécutée régulièrement (via cron)
 * pour respecter la politique de rétention des logs (5 ans par défaut)
 *
 * Usage: php bin/console app:purge-deletion-logs
 */
#[AsCommand(
    name: 'app:purge-deletion-logs',
    description: 'Purge les logs d\'audit de suppression d\'utilisateurs expirés (conformité RGPD)',
)]
class PurgeDeletionAuditLogsCommand extends Command
{
    public function __construct(
        private DeletionAuditService $deletionAuditService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp(
            'Cette commande supprime les logs d\'audit de suppression d\'utilisateurs ' .
            'dont la période de rétention est expirée (par défaut 5 ans). ' .
            PHP_EOL . PHP_EOL .
            'Conformité RGPD : Les logs d\'audit doivent être conservés pour prouver ' .
            'la conformité, mais doivent également être supprimés après une période définie ' .
            'pour respecter le principe de minimisation des données.' .
            PHP_EOL . PHP_EOL .
            'Il est recommandé d\'exécuter cette commande via un cron job mensuel ou trimestriel.' .
            PHP_EOL . PHP_EOL .
            'Exemple cron (chaque 1er du mois à 3h du matin):' . PHP_EOL .
            '0 3 1 * * cd /path/to/project && php bin/console app:purge-deletion-logs'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Purge des logs d\'audit de suppression expirés');

        $io->info('Recherche des logs expirés...');

        try {
            $count = $this->deletionAuditService->purgeExpiredLogs();

            if ($count === 0) {
                $io->success('Aucun log d\'audit expiré trouvé.');
            } else {
                $io->success(sprintf(
                    '%d log(s) d\'audit expiré(s) ont été supprimés avec succès.',
                    $count
                ));

                $io->note(
                    'Les logs supprimés concernaient des suppressions d\'utilisateurs ' .
                    'effectuées il y a plus de 5 ans.'
                );
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de la purge des logs d\'audit : ' . $e->getMessage());

            if ($output->isVerbose()) {
                $io->text($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }
}
