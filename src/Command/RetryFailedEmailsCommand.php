<?php

namespace App\Command;

use App\Entity\FailedEmail;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:retry-failed-emails',
    description: 'Retry sending failed emails',
)]
class RetryFailedEmailsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationMailerRegistry $notificationMailerRegistry,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var FailedEmail[] $failedEmails */
        $failedEmails = $this->entityManager->getRepository(FailedEmail::class)->findBy(['isResendSuccessful' => false]);

        foreach ($failedEmails as $failedEmail) {
            /** @var FailedEmail $failedEmail */
            $notificationMail = new NotificationMail(
                type: constant('App\Service\Mailer\NotificationMailerType::'.$failedEmail->getType()),
                to: $failedEmail->getToEmail(),
                fromEmail: $failedEmail->getFromEmail(),
                fromFullname: $failedEmail->getFromFullname(),
                params: $failedEmail->getParams(),
                signalement: $failedEmail->getSignalement(),
                suivi: $failedEmail->getSuivi(),
                signalementDraft: $failedEmail->getSignalementDraft(),
                message: $failedEmail->getMessage(),
                territory: $failedEmail->getTerritory(),
                user: $failedEmail->getUser(),
                intervention: $failedEmail->getIntervention(),
                previousVisiteDate: $failedEmail->getPreviousVisiteDate(),
                attachment: $failedEmail->getAttachment(),
                motif: $failedEmail->getMotif(),
                cronLabel: $failedEmail->getCronLabel(),
                cronCount: $failedEmail->getCronCount(),
            );

            $success = $this->notificationMailerRegistry->send($notificationMail, false);

            if ($success) {
                $io->success(sprintf('E-mail envoyé à %s', implode(', ', $failedEmail->getToEmail())));
                $failedEmail->setResendSuccessful(true);
            } else {
                $io->error(sprintf('E-mail non envoyé à %s', implode(', ', $failedEmail->getToEmail())));
                $failedEmail->setRetryCount($failedEmail->getRetryCount() + 1);
                $failedEmail->setLastAttemptAt(new \DateTimeImmutable());
                $failedEmail->setResendSuccessful(false);
            }
            $this->entityManager->persist($failedEmail);
        }

        $this->entityManager->flush();

        $io->success('Traitement des e-mails échoués terminé.');

        return Command::SUCCESS;
    }
}
