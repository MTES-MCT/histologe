<?php

namespace App\Command\Cron;

use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\Anonymizer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:anonymize-expired-signalement',
    description: 'Anonymizes expired signalement accounts 5 years after closure.',
)]
class AnonymizeExpiredSignalementCommand extends AbstractCronCommand
{
    private const int NB_YEARS_BEFORE_ANONYMIZATION = 5;

    private SymfonyStyle $io;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SignalementRepository $signalementRepository,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
        private readonly Anonymizer $anonymizer,
        #[Autowire(env: 'FEATURE_ANONYMIZE_EXPIRED_SIGNALEMENT')]
        private bool $featureAnonymizeExpiredSignalement,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        if (!$this->featureAnonymizeExpiredSignalement) {
            $this->io->warning('Feature "FEATURE_ANONYMIZE_EXPIRED_SIGNALEMENT" is disabled.');

            return Command::SUCCESS;
        }

        $nbSignalements = $this->anonymizeExpiredSignalements();

        $this->entityManager->flush();

        $this->io->success($nbSignalements.' signalements expirés anonymisés.');

        $message = $nbSignalements.' signalements expirés anonymisés.';

        if ($nbSignalements > 0) {
            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_CRON,
                    to: (string) $this->parameterBag->get('admin_email'),
                    message: $message,
                    cronLabel: 'Anonymisation de signalements expirés',
                )
            );
        }

        return Command::SUCCESS;
    }

    private function anonymizeExpiredSignalements(): int
    {
        $expirationDate = (new \DateTimeImmutable())->modify('-'.self::NB_YEARS_BEFORE_ANONYMIZATION.' years');
        $expiredSignalements = $this->signalementRepository->findExpiredSignalements($expirationDate);

        /** @var Signalement $signalement */
        foreach ($expiredSignalements as $signalement) {
            $this->anonymizer->anonymize($signalement);
        }

        return \count($expiredSignalements);
    }
}
