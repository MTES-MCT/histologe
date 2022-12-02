<?php

namespace App\Command;

use App\Entity\JobEvent;
use App\Manager\AffectationManager;
use App\Manager\JobEventManager;
use App\Repository\AffectationRepository;
use App\Service\Esabora\DossierResponse;
use App\Service\Esabora\EsaboraService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'app:esabora-sync',
    description: 'Commande qui permet de mettre à jour l\'état d\'une affectation depuis Esabora',
)]
class EsaboraSynchronizeCommand extends Command
{
    public function __construct(
        private EsaboraService $esaboraService,
        private AffectationManager $affectationManager,
        private JobEventManager $jobEventManager,
        private SerializerInterface $serializer,
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

        foreach ($affectations as $affectation) {
            $message = [
                'criterionName' => 'SAS_Référence',
                'criterionValueList' => [
                        $affectation->getSignalement()->getUuid(),
                ],
            ];

            /** @var DossierResponse $dossierResponse */
            $dossierResponse = $this->esaboraService->getStateDossier($affectation);
            if (200 === $dossierResponse->getStatusCode() && null !== $dossierResponse->getSasEtat()) {
                $this->affectationManager->synchronizeAffectationFrom($dossierResponse, $affectation);
                $io->success(
                    sprintf('SAS Référence: %s, SAS Etat: %s, ID: %s, Numéro: %s, Statut: %s, Etat: %s, Date Cloture: %s',
                        $dossierResponse->getSasReference(),
                        $dossierResponse->getSasEtat(),
                        $dossierResponse->getId(),
                        $dossierResponse->getNumero(),
                        $dossierResponse->getStatut(),
                        $dossierResponse->getEtat(),
                        $dossierResponse->getDateCloture()
                    )
                );
            }
            $jobEvent = $this->jobEventManager->createJobEvent(
                type: 'esabora',
                title: 'sync_dossier',
                message: json_encode($message, \JSON_THROW_ON_ERROR),
                response: $this->serializer->serialize($dossierResponse, 'json'),
                status: 200 === $dossierResponse->getStatusCode() ? JobEvent::STATUS_SUCCESS : JobEvent::STATUS_FAILED,
                signalementId: $affectation->getSignalement()->getId(),
                partnerId: $affectation->getPartner()->getId()
            );
        }

        return Command::SUCCESS;
    }
}
