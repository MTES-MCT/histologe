<?php

namespace App\Command\Temp;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\PartnerType;
use App\Repository\AffectationRepository;
use App\Service\Interconnection\Esabora\AbstractEsaboraService;
use App\Service\Interconnection\Esabora\Enum\EsaboraStatus;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Interconnection\Esabora\EsaboraSISHService;
use App\Service\Interconnection\Esabora\Response\DossierStateSISHResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:force-esabora-dossier-cloture',
    description: 'A la demande des ARS, force la clôture d\'un dossier Esabora SISH pour une affectation ARS.'
)]
class ForceEsaboraDossierClotureCommand extends Command
{
    public function __construct(
        private readonly EsaboraSISHService $esaboraSISHService,
        private readonly EsaboraManager $esaboraManager,
        private readonly AffectationRepository $affectationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('uuid_signalement', InputArgument::REQUIRED, 'Uuid signalement')
        ;
    }

    /**
     * @throws \DateMalformedStringException
     * @throws \DateInvalidTimeZoneException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $uuid = $input->getArgument('uuid_signalement');

        $affectations = $this->affectationRepository->findAffectationSubscribedToEsabora(
            partnerType: PartnerType::ARS,
            uuidSignalement: $uuid
        );

        if (!isset($affectations[0])) {
            $io->error(sprintf('Impossible de trouver l\'affectation pour le signalement %s', $uuid));

            return Command::INVALID;
        }
        /** @var Affectation $affectation */
        $affectation = $affectations[0]['affectation'];

        if (AffectationStatus::WAIT !== $affectation->getStatut()) {
            $io->error(sprintf('Le signalement %s doit être en cours.', $uuid));

            return Command::SUCCESS;
        }

        $dossierStateSISHResponse = $this->esaboraSISHService->getStateDossier($affectation, $uuid);
        if ($dossierStateSISHResponse->getSasEtat() === EsaboraStatus::ESABORA_REJECTED->value) {
            $this->esaboraManager->synchronizeAffectationFrom(
                dossierResponse: $dossierStateSISHResponse,
                affectation: $affectation
            );
            $io->success(sprintf(
                'Synchronisation classique effectuée : le signalement %s est clôturé pour ARS à partir du statut Santé Habitat.',
                $uuid
            ));

            return Command::SUCCESS;
        }

        if (null === $dossierStateSISHResponse->getSasEtat()) {
            $dossierRejete = new DossierStateSISHResponse($this->getDossierRejeteData($affectation), 200);

            $this->esaboraManager->synchronizeAffectationFrom(
                dossierResponse: $dossierRejete,
                affectation: $affectation
            );

            $this->entityManager->flush();

            $io->success(sprintf(
                'Clôture forcée effectuée : le signalement %s est clôturé pour ARS.',
                $uuid
            ));

            return Command::SUCCESS;
        }

        $io->warning('Ce signalement n\'est pas concernée par la clôture forcée.');

        return Command::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function getDossierRejeteData(Affectation $affectation): array
    {
        $signalement = $affectation->getSignalement();
        $message = 'Clôture forcée, dossier clôturé par ARS sur Santé Habitat '
            .'entre le vendredi 29 mai et le mercredi 3 juin 2026, '
            .'pendant l\'interruption des synchronisations. '
            .'Veuillez vous rapprocher de l\'ARS pour connaître le motif de clôture.';

        $dateCloture = new \DateTimeImmutable()->format(AbstractEsaboraService::FORMAT_DATE);

        return [
            'searchId' => '',
            'nbResults' => 1,
            'columnList' => [
                'Reference_Dossier',
                'Sas_Etat',
                'Sas_DateDecision',
                'Sas_CauseRefus',
                'SISH_DossId',
                'SISH_DossNum',
                'SISH_DossObjet',
                'SISH_DossDateCloture',
                'SISH_DossStatutAbr',
                'SISH_DossStatut',
                'SISH_DossEtat',
                'SISH_DossTypeCode',
                'SISH_DossTypeLib',
            ],
            'keyList' => [
                'i1.imdo_id',
            ],
            'rowList' => [
                [
                    'columnDataList' => [
                        $signalement->getUuid(),
                        EsaboraStatus::ESABORA_REJECTED->value,
                        $dateCloture,
                        $message,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                    ],
                    'keyDataList' => [
                        null,
                    ],
                ],
            ],
        ];
    }
}
