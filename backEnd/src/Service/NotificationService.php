<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Treatment;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private UserRepository $userRepository,
        private string $mailerFromAddress = 'noreply@rgpd-manager.fr',
        private string $mailerFromName = 'RGPD Manager'
    ) {}

    public function notifyTreatmentSubmitted(Treatment $treatment): void
    {
        $dpoUsers = $this->userRepository->findByRole('ROLE_DPO');

        foreach ($dpoUsers as $index => $dpo) {
            $notification = new Notification();
            $notification->setUser($dpo);
            $notification->setType('treatment_submitted');
            $notification->setTitle('Nouveau traitement à valider');
            $notification->setMessage(
                "Le traitement \"{$treatment->getNomTraitement()}\" a été soumis pour validation par {$treatment->getCreatedBy()->getEmail()}"
            );
            $notification->setTreatment($treatment);
            $notification->setData([
                'action' => 'validate',
                'treatmentId' => $treatment->getId()
            ]);

            $this->entityManager->persist($notification);

            $this->sendEmail(
                $dpo->getEmail(),
                '🔔 Nouveau traitement RGPD à valider',
                $this->getSubmittedEmailTemplate($treatment, $dpo)
            );

            // Délai de 1 seconde entre chaque email pour éviter les limites Mailtrap (2 emails/sec max)
            if ($index < count($dpoUsers) - 1) {
                usleep(1000000); // 1 seconde
            }
        }

        $this->entityManager->flush();
    }

    public function notifyTreatmentValidated(Treatment $treatment): void
    {
        $user = $treatment->getCreatedBy();

        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType('treatment_validated');
        $notification->setTitle('✅ Traitement validé');
        $notification->setMessage(
            "Votre traitement \"{$treatment->getNomTraitement()}\" a été validé par le DPO"
        );
        $notification->setTreatment($treatment);
        $notification->setData([
            'action' => 'view',
            'treatmentId' => $treatment->getId()
        ]);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        $this->sendEmail(
            $user->getEmail(),
            '✅ Votre traitement RGPD a été validé',
            $this->getValidatedEmailTemplate($treatment, $user)
        );
    }

    
    public function notifyTreatmentToModify(Treatment $treatment, string $comment): void
    {
        $user = $treatment->getCreatedBy();

        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType('treatment_to_modify');
        $notification->setTitle('✏️ Modification demandée');
        $notification->setMessage(
            "Des modifications sont demandées pour \"{$treatment->getNomTraitement()}\". Commentaire : {$comment}"
        );
        $notification->setTreatment($treatment);
        $notification->setData([
            'action' => 'edit',
            'treatmentId' => $treatment->getId(),
            'comment' => $comment
        ]);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        $this->sendEmail(
            $user->getEmail(),
            '✏️ Modifications demandées sur votre traitement RGPD',
            $this->getToModifyEmailTemplate($treatment, $user, $comment)
        );
    }

    private function sendEmail(string $to, string $subject, string $html): void
    {
        $email = (new Email())
            ->from(new \Symfony\Component\Mime\Address($this->mailerFromAddress, $this->mailerFromName))
            ->to($to)
            ->subject($subject)
            ->html($html);

        try {
            $this->mailer->send($email);
            error_log("✅ Email envoyé avec succès à {$to}: {$subject}");
        } catch (\Exception $e) {
            error_log("❌ Erreur envoi email à {$to}: " . $e->getMessage());
        }
    }

    private function getValidatedEmailTemplate(Treatment $treatment, User $user): string
    {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background-color: #10b981; color: white; padding: 20px; text-align: center;'>
                <h1>✅ Traitement Validé</h1>
            </div>
            <div style='padding: 20px; background-color: #f9fafb;'>
                <p>Bonjour,</p>
                <p>Votre traitement RGPD a été validé par le DPO.</p>
                <div style='background-color: white; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='color: #1f2937;'>Détails du traitement</h3>
                    <p><strong>Nom :</strong> {$treatment->getNomTraitement()}</p>
                    <p><strong>Service :</strong> {$treatment->getService()}</p>
                </div>
                <div style='text-align: center; margin-top: 30px;'>
                    <a href='http://localhost:3000/treatments/{$treatment->getId()}' 
                       style='background-color: #3b82f6; color: white; padding: 12px 24px; 
                              text-decoration: none; border-radius: 6px; display: inline-block;'>
                        Voir le traitement
                    </a>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    private function getRejectedEmailTemplate(Treatment $treatment, User $user, string $reason): string
    {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background-color: #ef4444; color: white; padding: 20px; text-align: center;'>
                <h1>❌ Traitement Refusé</h1>
            </div>
            <div style='padding: 20px; background-color: #f9fafb;'>
                <p>Bonjour,</p>
                <p>Votre traitement RGPD a été refusé par le DPO.</p>
                <div style='background-color: white; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <h3>Détails du traitement</h3>
                    <p><strong>Nom :</strong> {$treatment->getNomTraitement()}</p>
                </div>
                <div style='background-color: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0;'>
                    <h4 style='color: #991b1b;'>Raison du refus :</h4>
                    <p>{$reason}</p>
                </div>
                <div style='text-align: center; margin-top: 30px;'>
                    <a href='http://localhost:3000/treatments/{$treatment->getId()}/edit' 
                       style='background-color: #3b82f6; color: white; padding: 12px 24px; 
                              text-decoration: none; border-radius: 6px; display: inline-block;'>
                        Modifier le traitement
                    </a>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    private function getToModifyEmailTemplate(Treatment $treatment, User $user, string $comment): string
    {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background-color: #f59e0b; color: white; padding: 20px; text-align: center;'>
                <h1>✏️ Modifications Demandées</h1>
            </div>
            <div style='padding: 20px; background-color: #f9fafb;'>
                <p>Bonjour,</p>
                <p>Le DPO demande des modifications sur votre traitement RGPD.</p>
                <div style='background-color: white; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <h3>Détails du traitement</h3>
                    <p><strong>Nom :</strong> {$treatment->getNomTraitement()}</p>
                </div>
                <div style='background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0;'>
                    <h4 style='color: #92400e;'>Commentaires du DPO :</h4>
                    <p>{$comment}</p>
                </div>
                <div style='text-align: center; margin-top: 30px;'>
                    <a href='http://localhost:3000/treatments/{$treatment->getId()}/edit' 
                       style='background-color: #3b82f6; color: white; padding: 12px 24px; 
                              text-decoration: none; border-radius: 6px; display: inline-block;'>
                        Modifier le traitement
                    </a>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    private function getSubmittedEmailTemplate(Treatment $treatment, User $dpo): string
    {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background-color: #3b82f6; color: white; padding: 20px; text-align: center;'>
                <h1>🔔 Nouveau Traitement à Valider</h1>
            </div>
            <div style='padding: 20px; background-color: #f9fafb;'>
                <p>Bonjour,</p>
                <p>Un nouveau traitement RGPD a été soumis et attend votre validation.</p>
                <div style='background-color: white; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                    <h3>Détails du traitement</h3>
                    <p><strong>Nom :</strong> {$treatment->getNomTraitement()}</p>
                    <p><strong>Service :</strong> {$treatment->getService()}</p>
                    <p><strong>Créé par :</strong> {$treatment->getCreatedBy()->getEmail()}</p>
                </div>
                <div style='text-align: center; margin-top: 30px;'>
                    <a href='http://localhost:3000/dpo/dashboard' 
                       style='background-color: #10b981; color: white; padding: 12px 24px; 
                              text-decoration: none; border-radius: 6px; display: inline-block;'>
                        Examiner le traitement
                    </a>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}