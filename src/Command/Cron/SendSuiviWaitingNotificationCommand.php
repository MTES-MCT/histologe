<?php

namespace App\Command\Cron;

use App\Manager\SuiviManager;
use App\Repository\SuiviRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:send-suivi-waiting-notification',
    description: 'Send notifications for suivis with waitingNotification flag and expired time',
)]
class SendSuiviWaitingNotificationCommand extends AbstractCronCommand
{
    public function __construct(
        private readonly SuiviRepository $suiviRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ParameterBagInterface $parameterBag,
        private readonly SuiviManager $suiviManager,
    ) {
        parent::__construct($this->parameterBag);
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $suivis = $this->suiviRepository->findWithWaitingNotificationAndExpiredDelay();
        foreach ($suivis as $suivi) {
            $suivi->setWaitingNotification(false);
            $this->suiviManager->onSuiviCreated($suivi);
        }

        $this->entityManager->flush();
        $io->success('Les notifications de '.count($suivis).' suivis ont été envoyées avec succès.');

        return Command::SUCCESS;
    }
}
