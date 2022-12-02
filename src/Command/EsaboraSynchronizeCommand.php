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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'app:esabora-synchronize',
    description: 'Add a short description for your command',
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

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
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
            $this->affectationManager->synchronizeAffectationFrom($dossierResponse, $affectation);
            $jobEvent = $this->jobEventManager->createJobEvent(
                type: 'esabora',
                title: 'sync_dossier',
                message: json_encode($message, \JSON_THROW_ON_ERROR),
                response: $this->serializer->serialize($dossierResponse, 'json'),
                status: 200 === $dossierResponse->getStatusCode() ? JobEvent::STATUS_SUCCESS : JobEvent::STATUS_FAILED,
                signalementId: $affectation->getSignalement()->getId(),
                partnerId: $affectation->getPartner()->getId()
            );

            $io->success(
                sprintf('SAS Référence: %s, SAS Etat: %s, ID: %s, numéro %s, statut: %s, état: %s, cloture: %s',
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

        return Command::SUCCESS;
    }
}
