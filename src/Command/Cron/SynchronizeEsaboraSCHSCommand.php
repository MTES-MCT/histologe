<?php

namespace App\Command\Cron;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Entity\Suivi;
use App\Manager\JobEventManager;
use App\Repository\AffectationRepository;
use App\Repository\SuiviRepository;
use App\Service\Esabora\EsaboraManager;
use App\Service\Esabora\EsaboraSCHSService;
use App\Service\Mailer\NotificationMailerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
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
                $response = $this->esaboraService->getEventsDossier($affectation);
                $statusCode = $response->getStatusCode();
                if (Response::HTTP_OK !== $statusCode) {
                    throw new \Exception('status code : '.$statusCode);
                }
                $dataResponse = $response->toArray();
                if (!isset($dataResponse['rowList']) || empty($dataResponse['rowList'])) {
                    continue;
                }
                foreach ($dataResponse['rowList'] as $event) {
                    $this->synchronizeEvent($event, $affectation);
                }
                $this->entityManager->flush();
            } catch (\Throwable $exception) {
                $msg = sprintf('Error while synchronizing events on signalement %s: %s', $affectation->getSignalement()->getUuid(), $exception->getMessage());
                $this->io->error($msg);
                $this->logger->error($msg);
            }
        }
        $this->io->success(sprintf('Synchronized %d new events', $this->nbEventsAdded));
    }

    protected function synchronizeEvent(array $event, Affectation $affectation): void
    {
        // données de l'événenement (dans la clé "columnDataList") :
        // - reference histologe
        // - date
        // - description
        // - nom des pieces jointe (séparé par des virgules)
        // - type d'événement
        // id techniques (dans la clé "keyDataList") :
        // - Identifiant technique de l'import dans le sas
        // - Identifiant technique de l’évènement dans le sas
        if (!isset($this->existingEvents[$event['keyDataList'][1]])) {
            $suivi = new Suivi();
            $suivi->setSignalement($affectation->getSignalement());
            $suivi->setType(Suivi::TYPE_PARTNER); // quel type de suivi faut-il utiliser ?
            $suivi->setContext(Suivi::CONTEXT_SCHS);
            $suivi->setDescription($event['columnDataList'][2]);
            $suivi->setCreatedAt(\DateTimeImmutable::createFromFormat('d/m/Y', $event['columnDataList'][1]));
            $suivi->setOriginalData($event);
            $this->entityManager->persist($suivi);
            ++$this->nbEventsAdded;
        }
    }
}
