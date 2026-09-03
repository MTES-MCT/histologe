<?php

namespace App\Command\Temp;

use App\Entity\Enum\SignalementStatus;
use App\Repository\SignalementRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'app:regenerate-code-suivi',
    description: 'Régénère le code suivi des 2000 premiers signalements. Envoie le nouveau lien de suivi aux usagers pour les signalements toujours actifs',
)]
class RegenerateCodeSuiviCommand extends Command
{
    private const int LIMIT = 2000;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SignalementRepository $signalementRepository,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $signalements = $this->signalementRepository->createQueryBuilder('s')->orderBy('s.id', 'ASC')->setMaxResults(self::LIMIT)->getQuery()->getResult();
        $io->info(sprintf('%d signalements à traiter.', count($signalements)));

        $countMailSent = 0;
        $sentCountByEmail = [];

        $io->progressStart(count($signalements));
        foreach ($signalements as $signalement) {
            $signalement->setCodeSuivi(Uuid::v4());
            $io->progressAdvance();
        }
        $this->entityManager->flush();
        $io->progressFinish();
        $io->info('Codes suivi régénérés et enregistrés.');

        $io->progressStart(count($signalements));
        foreach ($signalements as $signalement) {
            $emails = $signalement->getMailUsagers();
            if (SignalementStatus::ACTIVE === $signalement->getStatut() && !empty($emails)) {
                $this->notificationMailerRegistry->send(
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_TEMP_REGENERATED_CODE_SUIVI,
                        to: $emails,
                        signalement: $signalement,
                    )
                );
                ++$countMailSent;
                foreach ($emails as $email) {
                    $sentCountByEmail[$email] = ($sentCountByEmail[$email] ?? 0) + 1;
                }
            }
            $io->progressAdvance();
        }
        $io->progressFinish();

        $io->success(sprintf('%d e-mails envoyés.', $countMailSent));

        $multiSent = array_filter($sentCountByEmail, static fn (int $count) => $count > 1);
        if (!empty($multiSent)) {
            arsort($multiSent);
            $rows = [];
            foreach ($multiSent as $email => $count) {
                $rows[] = [$email, $count];
            }
            $io->section('Adresses e-mail ayant reçu plusieurs e-mails');
            $io->table(['E-mail', 'Nombre d\'e-mails'], $rows);
        }

        return Command::SUCCESS;
    }
}
