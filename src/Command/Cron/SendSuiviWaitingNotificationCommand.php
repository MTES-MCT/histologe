<?php

namespace App\Command\Cron;

use App\Event\SuiviCreatedEvent;
use App\Manager\SuiviManager;
use App\Repository\SuiviDelayedRepository;
use App\Repository\SuiviRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:send-suivi-waiting-notification-and-delayed',
    description: '
        - Send notifications for suivis with waitingNotification flag and expired time.
        - Generate suivis from SuiviDelayed with expired time.',
)]
class SendSuiviWaitingNotificationCommand extends AbstractCronCommand
{
    public function __construct(
        private readonly SuiviRepository $suiviRepository,
        private readonly SuiviDelayedRepository $suiviDelayedRepository,
        private readonly SuiviManager $suiviManager,
        private readonly EntityManagerInterface $entityManager,
        private readonly ParameterBagInterface $parameterBag,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct($this->parameterBag);
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $this->manageSuiviWaitingNotification($io);
        $this->manageSuiviDelayed($io);

        return Command::SUCCESS;
    }

    private function manageSuiviWaitingNotification(SymfonyStyle $io): void
    {
        $suivis = $this->suiviRepository->findWithWaitingNotificationAndExpiredDelay();
        foreach ($suivis as $suivi) {
            $suivi->setWaitingNotification(false);
            $this->eventDispatcher->dispatch(new SuiviCreatedEvent($suivi), SuiviCreatedEvent::NAME); // @phpstan-ignore-line
        }
        $this->entityManager->flush();
        $io->success('Les notifications de '.count($suivis).' suivis ont été envoyées avec succès.');
    }

    private function manageSuiviDelayed(SymfonyStyle $io): void
    {
        $suivisDelayed = $this->suiviDelayedRepository->findSuiviDelayedWithExpiredDelay();

        $groups = [];
        foreach ($suivisDelayed as $suiviDelayed) {
            $key = $suiviDelayed->getUser()->getId().'_'.$suiviDelayed->getSignalement()->getId().'_'.$suiviDelayed->getSuiviCategory()->value;
            $groups[$key][] = $suiviDelayed;
            // comment for debug
            $this->entityManager->remove($suiviDelayed);
        }

        foreach ($groups as $list) {
            $this->suiviManager->createSuiviFromSuiviDelayedList($list);
        }

        $this->entityManager->flush();
        $io->success(count($groups).' suivis générés pour '.count($suivisDelayed).' suivi différés traités.');
    }
}
