<?php

namespace App\Command\Cron;

use App\Event\SuiviCreatedEvent;
use App\Repository\SuiviRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
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
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($this->parameterBag);
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $suivis = $this->suiviRepository->findWithWaitingNotificationAndExpiredDelay();
        foreach ($suivis as $suivi) {
            $suivi->setWaitingNotification(false);
            $this->eventDispatcher->dispatch(new SuiviCreatedEvent($suivi), SuiviCreatedEvent::NAME); // @phpstan-ignore-line
        }

        $this->entityManager->flush();
        $io->success('Les notifications de '.count($suivis).' suivis ont été envoyées avec succès.');

        return Command::SUCCESS;
    }
}
