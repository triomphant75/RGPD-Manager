<?php

namespace App\Command;

use App\Entity\DataBreachIncident;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:breach:remind-notification',
    description: 'Rappelle les incidents nécessitant une notification (<72h)'
)]
class BreachNotificationReminderCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repo = $this->em->getRepository(DataBreachIncident::class);
        $incidents = $repo->findBy(['notificationRequired' => true], ['id' => 'DESC']);
        $now = new \DateTimeImmutable();

        $count = 0;
        foreach ($incidents as $incident) {
            if ($incident->getAuthorityNotifiedAt()) continue;
            // si >48h depuis detectedAt, rappeler (la deadline est 72h)
            $elapsed = $now->getTimestamp() - $incident->getDetectedAt()->getTimestamp();
            if ($elapsed > 48 * 3600) {
                $count++;
                $output->writeln(sprintf('[REMINDER] Incident #%d doit être notifié avant 72h (detectedAt=%s)', $incident->getId(), $incident->getDetectedAt()->format(DATE_ATOM)));
            }
        }

        $output->writeln(sprintf('Rappels envoyés: %d', $count));
        return Command::SUCCESS;
    }
}
