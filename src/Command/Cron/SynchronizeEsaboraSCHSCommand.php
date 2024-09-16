<?php

namespace App\Command\Cron;

use App\Entity\Enum\PartnerType;
use App\Entity\Suivi;
use App\Manager\JobEventManager;
use App\Repository\AffectationRepository;
use App\Repository\SuiviRepository;
use App\Service\Esabora\EsaboraManager;
use App\Service\Esabora\EsaboraSCHSService;
use App\Service\Esabora\Response\Model\DossierEventSCHS;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'app:sync-esabora-schs',
    description: '[SCHS] Commande qui permet de mettre à jour l\'état d\'une affectation depuis Esabora',
)]
class SynchronizeEsaboraSCHSCommand extends AbstractSynchronizeEsaboraCommand
{
    private SymfonyStyle $io;
    private array $existingEvents = [];
    private int $nbEventsAdded = 0;
    private int $nbEventFilesAdded = 0;

    public function __construct(
        private readonly EsaboraSCHSService $esaboraService,
        private readonly EsaboraManager $esaboraManager,
        private readonly JobEventManager $jobEventManager,
        private readonly AffectationRepository $affectationRepository,
        private readonly SerializerInterface $serializer,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
        private readonly LoggerInterface $logger,
        private readonly SuiviRepository $suiviRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct(
            $this->esaboraManager,
            $this->jobEventManager,
            $this->affectationRepository,
            $this->serializer,
            $this->notificationMailerRegistry,
            $this->parameterBag,
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->synchronizeStatus(
            $input,
            $output,
            $this->esaboraService,
            PartnerType::COMMUNE_SCHS,
            'SAS_Référence'
        );

        $this->synchronizeEvents();

        return Command::SUCCESS;
    }

    protected function synchronizeEvents(): void
    {
        $affectations = $this->affectationRepository->findAffectationSubscribedToEsabora(partnerType: PartnerType::COMMUNE_SCHS, isSynchronized: true);
        $this->existingEvents = $this->suiviRepository->findExistingEventsForSCHS();
        foreach ($affectations as $affectation) {
            try {
                $dossierEvents = $this->esaboraService->getDossierEvents($affectation);
                foreach ($dossierEvents->getEvents() as $event) {
                    $this->synchronizeEvent($event);
                }
                $this->entityManager->flush();
            } catch (\Throwable $exception) {
                $msg = sprintf('Error while synchronizing events on signalement %s: %s', $affectation->getSignalement()->getUuid(), $exception->getMessage());
                $this->io->error($msg);
                $this->logger->error($msg);
            }
        }
        $msg = sprintf('Synchronized %d new events with %d files', $this->nbEventsAdded, $this->nbEventFilesAdded);
        $this->io->success($msg);
        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: $this->parameterBag->get('admin_email'),
                message: $msg,
                cronLabel: '[SCHS] Synchronisation des évènements depuis Esabora',
            )
        );
    }

    protected function synchronizeEvent(DossierEventSCHS $event): bool
    {
        if (isset($this->existingEvents[$event->getEventId()])) {
            return false;
        }
        $suivi = $this->esaboraManager->createSuiviFromDossierEvent($event);
        ++$this->nbEventsAdded;
        if (!empty($event->getPiecesJointes())) {
            $this->synchronizeEventFiles($suivi, $event);
        }

        return true;
    }

    protected function synchronizeEventFiles(Suivi $suivi, DossierEventSCHS $event): bool
    {
        try {
            $dossierEventFiles = $this->esaboraService->getDossierEventFiles($event);
            $this->nbEventFilesAdded += $this->esaboraManager->addFilesToSuiviFromDossierEventFiles($dossierEventFiles, $suivi);
        } catch (\Throwable $exception) {
            $msg = sprintf('Error while synchronizing events files on signalement %s: %s', $event->getDossierEvents()->getAffectation()->getSignalement()->getUuid(), $exception->getMessage());
            $this->io->error($msg);
            $this->logger->error($msg);
        }

        return true;
    }
}
