<?php

namespace App\Command\Cron;

use App\Entity\Enum\PartnerType;
use App\Entity\JobEvent;
use App\Manager\AffectationManager;
use App\Manager\JobEventManager;
use App\Repository\AffectationRepository;
use App\Service\Esabora\AbstractEsaboraService;
use App\Service\Esabora\EsaboraSCHSService;
use App\Service\Mailer\NotificationMailerRegistry;
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
<<<<<<<< HEAD:src/Command/Cron/SynchronizeEsaboraCommand.php
class SynchronizeEsaboraCommand extends AbstractCronCommand
{
    public function __construct(
        private readonly EsaboraSCHSService $esaboraService,
        private readonly AffectationManager $affectationManager,
        private readonly JobEventManager $jobEventManager,
        private readonly SerializerInterface $serializer,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        parent::__construct($this->parameterBag);
========
class SynchronizeEsaboraSCHSCommand extends AbstractSynchronizeEsaboraCommand
{
    public function __construct(
        private EsaboraSCHSService $esaboraService,
        private AffectationManager $affectationManager,
        private JobEventManager $jobEventManager,
        private SerializerInterface $serializer,
        private NotificationMailerRegistry $notificationMailerRegistry,
        private ParameterBagInterface $parameterBag,
        string $name = 'app:sync-esabora-schs'
    ) {
        parent::__construct($this->notificationMailerRegistry, $this->parameterBag, $name);
>>>>>>>> eb38b489 (implement get state dossier sih #1119):src/Command/SynchronizeEsaboraSCHSCommand.php
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var AffectationRepository $affectationRepository */
        $affectationRepository = $this->affectationManager->getRepository();
        $affectations = $affectationRepository->findAffectationSubscribedToEsabora(PartnerType::COMMUNE_SCHS);
        $countSyncSuccess = 0;
        $countSyncFailed = 0;
        foreach ($affectations as $affectation) {
            $dossierResponse = $this->esaboraService->getStateDossier($affectation);
            if ($this->hasSuccess($dossierResponse)) {
                $this->affectationManager->synchronizeAffectationFrom($dossierResponse, $affectation);
                $io->success($this->printInfoSCHS($dossierResponse));
                ++$countSyncSuccess;
            } else {
                $io->error(sprintf('%s', $this->serializer->serialize($dossierResponse, 'json')));
                ++$countSyncFailed;
            }
            $this->jobEventManager->createJobEvent(
                service: AbstractEsaboraService::TYPE_SERVICE,
                action: AbstractEsaboraService::ACTION_SYNC_DOSSIER,
                message: json_encode($this->getMessage($affectation, 'SAS_Référence')),
                response: $this->serializer->serialize($dossierResponse, 'json'),
                status: $this->hasSuccess($dossierResponse)
                    ? JobEvent::STATUS_SUCCESS
                    : JobEvent::STATUS_FAILED,
                codeStatus: $dossierResponse->getStatusCode(),
                signalementId: $affectation->getSignalement()->getId(),
                partnerId: $affectation->getPartner()->getId(),
                partnerType: $affectation->getPartner()->getType(),
            );
        }
        $this->notify($countSyncSuccess, $countSyncFailed);

        return Command::SUCCESS;
    }
}
