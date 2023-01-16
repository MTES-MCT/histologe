<?php

namespace App\Command;

use App\Entity\JobEvent;
use App\Manager\AffectationManager;
use App\Manager\JobEventManager;
use App\Repository\AffectationRepository;
use App\Service\Esabora\DossierResponse;
use App\Service\Esabora\EsaboraService;
use App\Service\NotificationService;
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
class SynchronizeEsaboraCommand extends Command
{
    public function __construct(
        private EsaboraService $esaboraService,
        private AffectationManager $affectationManager,
        private JobEventManager $jobEventManager,
        private SerializerInterface $serializer,
        private NotificationService $notificationService,
        private ParameterBagInterface $parameterBag,
        string $name = null
    ) {
        parent::__construct($name);
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

            /** @var DossierResponse $dossierResponse */
            $dossierResponse = $this->esaboraService->getStateDossier($affectation);
            if (Response::HTTP_OK === $dossierResponse->getStatusCode() && null !== $dossierResponse->getSasEtat()) {
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
                ++$countSyncFailed;
            }
            $this->jobEventManager->createJobEvent(
                type: 'esabora',
                title: 'sync_dossier',
                message: json_encode($message),
                response: $this->serializer->serialize($dossierResponse, 'json'),
                status: Response::HTTP_OK === $dossierResponse->getStatusCode()
                    ? JobEvent::STATUS_SUCCESS
                    : JobEvent::STATUS_FAILED,
                signalementId: $affectation->getSignalement()->getId(),
                partnerId: $affectation->getPartner()->getId()
            );
        }

        $this->notificationService->send(
            NotificationService::TYPE_CRON,
            $this->parameterBag->get('admin_email'),
            [
                'url' => $this->parameterBag->get('host_url'),
                'cron_label' => 'Synchronisation des signalements depuis Esabora',
                'count_success' => $countSyncSuccess,
                'count_failed' => $countSyncFailed,
                'message_success' => $countSyncSuccess > 1
                    ? 'signalements ont été synchronisés'
                    : 'signalement a été synchronisé',
                'message_failed' => $countSyncFailed > 1
                    ? 'signalements n\'ont été synchronisés'
                    : 'signalement n\'a pas été synchronisé',
            ],
            null
        );

        return Command::SUCCESS;
    }
}
