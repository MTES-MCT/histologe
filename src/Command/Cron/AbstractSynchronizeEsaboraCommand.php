<?php

namespace App\Command\Cron;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Entity\JobEvent;
use App\Manager\JobEventManager;
use App\Repository\AffectationRepository;
use App\Service\Esabora\AbstractEsaboraService;
use App\Service\Esabora\EsaboraManager;
use App\Service\Esabora\EsaboraServiceInterface;
use App\Service\Esabora\Response\DossierCollectionResponseInterface;
use App\Service\Esabora\Response\DossierResponseInterface;
use App\Service\Esabora\Response\DossierStateSCHSResponse;
use App\Service\Esabora\Response\DossierStateSISHResponse;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'app:sync-esabora',
    description: 'Commande qui permet de mettre à jour l\'état d\'une affectation depuis Esabora',
)]
class AbstractSynchronizeEsaboraCommand extends AbstractCronCommand
{
    public function __construct(
        private readonly EsaboraManager $esaboraManager,
        private readonly JobEventManager $jobEventManager,
        private readonly AffectationRepository $affectationRepository,
        private readonly SerializerInterface $serializer,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Please execute app:sync-esabora-sish or app:sync-esabora-schs');

        return Command::FAILURE;
    }

    protected function synchronizeStatus(
        InputInterface $input,
        OutputInterface $output,
        EsaboraServiceInterface $esaboraService,
        PartnerType $partnerType,
        string $criterionName,
    ): void {
        $io = new SymfonyStyle($input, $output);

        $affectations = $this->affectationRepository->findAffectationSubscribedToEsabora($partnerType);
        $countSyncSuccess = 0;
        $countSyncFailed = 0;
        foreach ($affectations as $affectation) {
            $dossierResponse = $esaboraService->getStateDossier($affectation);
            if ($this->hasSuccess($dossierResponse)) {
                $this->esaboraManager->synchronizeAffectationFrom($dossierResponse, $affectation);
                $io->success($this->printInfo($dossierResponse));
                ++$countSyncSuccess;
            } else {
                $io->error(sprintf('%s', $this->serializer->serialize($dossierResponse, 'json')));
                ++$countSyncFailed;
            }
            $this->jobEventManager->createJobEvent(
                service: AbstractEsaboraService::TYPE_SERVICE,
                action: AbstractEsaboraService::ACTION_SYNC_DOSSIER,
                message: json_encode($this->getMessage($affectation, $criterionName)),
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
        $this->notify($partnerType, $countSyncSuccess, $countSyncFailed);
    }

    protected function hasSuccess(DossierResponseInterface|DossierCollectionResponseInterface $dossierResponse): bool
    {
        return Response::HTTP_OK === $dossierResponse->getStatusCode()
            && null !== $dossierResponse->getSasEtat()
            && null === $dossierResponse->getErrorReason();
    }

    protected function getMessage(Affectation $affectation, string $criterionName): array
    {
        return [
            'criterionName' => $criterionName,
            'criterionValueList' => [
                $affectation->getSignalement()->getUuid(),
            ],
        ];
    }

    protected function printInfo(DossierResponseInterface $dossierResponse): string
    {
        if ($dossierResponse instanceof DossierStateSISHResponse) {
            return $this->printInfoSISH($dossierResponse);
        }

        return $this->printInfoSCHS($dossierResponse);
    }

    protected function printInfoSISH(DossierStateSISHResponse $dossierStateSISHResponse): string
    {
        return sprintf(
            'Référence Dossier: %s, SAS Etat: %s, Date décision: %s,Cause refus: %s, ID technique: %s,
            N°dossier: %s, Objet: %s, Date Cloture: %s, DossStatutAbr: %s, DossStatut: %s, DossEtat: %s,
            DossTypeCode: %s, DossTypeLib: %s',
            $dossierStateSISHResponse->getReferenceDossier(),
            $dossierStateSISHResponse->getSasEtat(),
            $dossierStateSISHResponse->getSasDateDecision(),
            $dossierStateSISHResponse->getSasCauseRefus(),
            $dossierStateSISHResponse->getDossId(),
            $dossierStateSISHResponse->getDossNum(),
            $dossierStateSISHResponse->getDossObjet(),
            $dossierStateSISHResponse->getDossDateCloture(),
            $dossierStateSISHResponse->getDossStatutAbr(),
            $dossierStateSISHResponse->getDossStatut(),
            $dossierStateSISHResponse->getDossEtat(),
            $dossierStateSISHResponse->getDossTypeCode(),
            $dossierStateSISHResponse->getDossTypeLib(),
        );
    }

    protected function printInfoSCHS(DossierStateSCHSResponse $dossierStateSCHSResponse): string
    {
        return sprintf(
            'SAS Référence: %s, SAS Etat: %s, ID: %s, Numéro: %s, Statut: %s, Etat: %s, Date Cloture: %s',
            $dossierStateSCHSResponse->getSasReference(),
            $dossierStateSCHSResponse->getSasEtat(),
            $dossierStateSCHSResponse->getId(),
            $dossierStateSCHSResponse->getNumero(),
            $dossierStateSCHSResponse->getStatut(),
            $dossierStateSCHSResponse->getEtat(),
            $dossierStateSCHSResponse->getDateCloture()
        );
    }

    protected function notify(PartnerType $partnerType, int $countSyncSuccess, int $countSyncFailed): void
    {
        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: $this->parameterBag->get('admin_email'),
                cronLabel: '['.$partnerType->value.'] Synchronisation des signalements depuis Esabora',
                params: [
                    'count_success' => $countSyncSuccess,
                    'count_failed' => $countSyncFailed,
                    'message_success' => $countSyncSuccess > 1
                        ? 'signalements ont été synchronisés'
                        : 'signalement a été synchronisé',
                    'message_failed' => $countSyncFailed > 1
                        ? 'signalements n\'ont pas été synchronisés'
                        : 'signalement n\'a pas été synchronisé',
                ],
            )
        );
    }
}
