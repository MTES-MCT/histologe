<?php

namespace App\Command;

use App\Entity\Affectation;
use App\Service\Esabora\Response\DossierResponseInterface;
use App\Service\Esabora\Response\DossierStateSCHSResponse;
use App\Service\Esabora\Response\DossierStateSISHResponse;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;

class AbstractSynchronizeEsaboraCommand extends Command
{
    public function __construct(
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('app:sync-esabora');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Please extends AbstractSynchronizeEsaboraCommand');

        return Command::FAILURE;
    }

    protected function hasSuccess(DossierResponseInterface $dossierResponse): bool
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

    protected function notify(int $countSyncSuccess, int $countSyncFailed): void
    {
        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_CRON,
                to: $this->parameterBag->get('admin_email'),
                cronLabel: 'Synchronisation des signalements depuis Esabora',
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
