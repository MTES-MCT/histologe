<?php

namespace App\Command\Cron;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Repository\AffectationRepository;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Interconnection\Esabora\EsaboraServiceInterface;
use App\Service\Interconnection\Esabora\Response\DossierResponseInterface;
use App\Service\Interconnection\Esabora\Response\DossierStateSCHSResponse;
use App\Service\Interconnection\Esabora\Response\DossierStateSISHResponse;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'app:sync-esabora',
    description: 'Commande qui permet de mettre à jour l\'état d\'une affectation depuis Esabora',
)]
class AbstractSynchronizeEsaboraCommand extends AbstractCronCommand
{
    public const int BATCH_SIZE = 100;

    public function __construct(
        private readonly EsaboraManager $esaboraManager,
        private readonly AffectationRepository $affectationRepository,
        private readonly SerializerInterface $serializer,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function configure(): void
    {
        $this->addArgument(
            'uuid_signalement',
            InputArgument::OPTIONAL,
            'Which signalement do you want to sync'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Please execute app:sync-esabora-sish or app:sync-esabora-schs');

        return Command::FAILURE;
    }

    /**
     * @param PartnerType|PartnerType[] $partnerType
     *
     * @throws ExceptionInterface
     */
    protected function synchronizeStatus(
        InputInterface $input,
        OutputInterface $output,
        EsaboraServiceInterface $esaboraService,
        PartnerType|array $partnerType,
    ): void {
        $io = new SymfonyStyle($input, $output);
        $uuidSignalement = $input->getArgument('uuid_signalement') ?? null;
        $partnerTypes = is_array($partnerType) ? $partnerType : [$partnerType];
        $affectations = $this->affectationRepository->findAffectationSubscribedToEsabora(
            partnerType: $partnerTypes,
            uuidSignalement: $uuidSignalement
        );
        $countSyncSuccess = 0;
        $countSyncFailed = 0;
        $count = 0;
        foreach ($affectations as $row) {
            $affectation = $row['affectation'];
            $dossierResponse = $esaboraService->getStateDossier($affectation, $row['signalement_uuid']);
            if (AbstractEsaboraService::hasSuccess($dossierResponse)) {
                $this->esaboraManager->synchronizeAffectationFrom($dossierResponse, $affectation);
                $io->success($this->printInfo($dossierResponse));
                ++$countSyncSuccess;
            } else {
                $io->error(\sprintf('%s', $this->serializer->serialize($dossierResponse, 'json')));
                ++$countSyncFailed;
            }
            ++$count;
            if (0 === $count % self::BATCH_SIZE) {
                $this->entityManager->flush();
            }
        }
        $this->entityManager->flush();
        $io->table(['Count success', 'Count Failed'], [[$countSyncSuccess, $countSyncFailed]]);
        $this->notify($partnerType, $countSyncSuccess, $countSyncFailed);
    }

    /**
     * @return array{criterionName: string, criterionValueList: string[]}
     */
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

        if ($dossierResponse instanceof DossierStateSCHSResponse) {
            return $this->printInfoSCHS($dossierResponse);
        }

        return 'error';
    }

    protected function printInfoSISH(DossierStateSISHResponse $dossierStateSISHResponse): string
    {
        return \sprintf(
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
        return \sprintf(
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

    /**
     * @param PartnerType|PartnerType[] $partnerType
     */
    protected function notify(
        PartnerType|array $partnerType,
        int $countSyncSuccess,
        int $countSyncFailed,
    ): void {
        $partnerTypeLabel = is_array($partnerType)
            ? implode('-', array_map(fn (PartnerType $type) => $type->value, $partnerType))
            : $partnerType->value;

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: (string) $this->parameterBag->get('admin_email'),
                cronLabel: '['.$partnerTypeLabel.'] Synchronisation des signalements depuis Esabora',
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
