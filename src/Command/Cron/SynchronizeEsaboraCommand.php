<?php

namespace App\Command\Cron;

use App\Entity\JobEvent;
use App\Manager\AffectationManager;
use App\Manager\JobEventManager;
use App\Repository\AffectationRepository;
use App\Service\Esabora\DossierResponse;
use App\Service\Esabora\EsaboraService;
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
class SynchronizeEsaboraCommand extends AbstractCronCommand
{
    public function __construct(
        private readonly EsaboraService $esaboraService,
        private readonly AffectationManager $affectationManager,
        private readonly JobEventManager $jobEventManager,
        private readonly SerializerInterface $serializer,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        parent::__construct($this->parameterBag);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var AffectationRepository $affectationRepository */
        $affectationRepository = $this->affectationManager->getRepository();
        $affectations = $affectationRepository->findAffectationSubscribedToEsabora();
        $countSyncSuccess = 0;
        $countSyncFailed = 0;
        foreach ($affectations as $affectation) {
            $message = [
                'criterionName' => 'SAS_Référence',
                'criterionValueList' => [
                    $affectation->getSignalement()->getUuid(),
                ],
            ];

            $dossierResponse = $this->esaboraService->getStateDossier($affectation);
            if ($this->hasSuccess($dossierResponse)) {
                $this->affectationManager->synchronizeAffectationFrom($dossierResponse, $affectation);
                $io->success(
                    sprintf(
                        'SAS Référence: %s, SAS Etat: %s, ID: %s, Numéro: %s, Statut: %s, Etat: %s, Date Cloture: %s',
                        $dossierResponse->getSasReference(),
                        $dossierResponse->getSasEtat(),
                        $dossierResponse->getId(),
                        $dossierResponse->getNumero(),
                        $dossierResponse->getStatut(),
                        $dossierResponse->getEtat(),
                        $dossierResponse->getDateCloture()
                    )
                );
                ++$countSyncSuccess;
            } else {
                $io->error(sprintf('%s', $this->serializer->serialize($dossierResponse, 'json')));
                ++$countSyncFailed;
            }
            $this->jobEventManager->createJobEvent(
                type: EsaboraService::TYPE_SERVICE,
                title: EsaboraService::ACTION_SYNC_DOSSIER,
                message: json_encode($message),
                response: $this->serializer->serialize($dossierResponse, 'json'),
                status: $this->hasSuccess($dossierResponse)
                    ? JobEvent::STATUS_SUCCESS
                    : JobEvent::STATUS_FAILED,
                signalementId: $affectation->getSignalement()->getId(),
                partnerId: $affectation->getPartner()->getId()
            );
        }

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

        return Command::SUCCESS;
    }

    private function hasSuccess(DossierResponse $dossierResponse): bool
    {
        return Response::HTTP_OK === $dossierResponse->getStatusCode()
            && null !== $dossierResponse->getSasEtat()
            && null === $dossierResponse->getErrorReason();
    }
}
