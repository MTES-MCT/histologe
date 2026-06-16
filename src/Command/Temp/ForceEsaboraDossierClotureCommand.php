<?php

namespace App\Command\Temp;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\PartnerType;
use App\Repository\AffectationRepository;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Service\Import\CsvParser;
use App\Service\Interconnection\Esabora\Enum\EsaboraStatus;
use App\Service\Interconnection\Esabora\EsaboraManager;
use App\Service\Interconnection\Esabora\Response\DossierStateSISHResponse;
use App\Service\UploadHandlerService;
use Doctrine\ORM\Query\QueryException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:force-esabora-dossier-cloture',
    description: 'A la demande des ARS, force la clôture d\'un dossier Esabora SISH pour une affectation ARS.'
)]
class ForceEsaboraDossierClotureCommand extends Command
{
    public function __construct(
        private readonly EsaboraManager $esaboraManager,
        private readonly AffectationRepository $affectationRepository,
        private readonly UploadHandlerService $uploadHandlerService,
        private readonly ParameterBagInterface $parameterBag,
        private readonly FilesystemOperator $fileStorage,
        private readonly CsvParser $csvParser,
        private readonly SignalementRepository $signalementRepository,
        private readonly TerritoryRepository $territoryRepository,
    ) {
        parent::__construct();
    }

    /**
     * @throws \DateMalformedStringException
     * @throws \DateInvalidTimeZoneException
     * @throws FilesystemException
     * @throws QueryException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fromFile = 'csv/rejet_sante_habitat.csv';
        $toFile = $this->parameterBag->get('uploads_tmp_dir').'csv/rejet_sante_habitat.csv';
        if (!$this->fileStorage->fileExists($fromFile)) {
            $io->error('CSV File does not exists');

            return Command::FAILURE;
        }

        $toDirectory = \dirname($toFile);
        if (!is_dir($toDirectory)) {
            mkdir($toDirectory, 0775, true);
        }

        $this->uploadHandlerService->createTmpFileFromBucket($fromFile, $toFile);

        $rows = $this->csvParser->parseAsDict($toFile);

        $territories = $this->territoryRepository->findAllIndexedByZip();
        foreach ($rows as $row) {
            $zip = filter_var($row['DD'], \FILTER_SANITIZE_NUMBER_INT);
            if (!is_string($zip) || !isset($territories[$zip])) {
                $io->error(sprintf('Territoire introuvable pour le département "%s"', $row['DD']));

                continue;
            }
            $signalement = $this->signalementRepository->findOneBy([
                'reference' => $row['SILO n°'],
                'territory' => $territories[$zip],
            ]);

            if (null === $signalement) {
                $io->warning(sprintf('Impossible de trouver le signalement (%s).', implode(', ', $row)));
                continue;
            }

            $affectations = $this->affectationRepository->findAffectationSubscribedToEsabora(
                partnerType: PartnerType::ARS,
                uuidSignalement: $uuid = $signalement->getUuid()
            );

            if (!isset($affectations[0])) {
                $io->warning(sprintf('Impossible de trouver l\'affectation pour le signalement %s, plus d\'ARS.', $uuid));

                continue;
            }

            /** @var Affectation $affectation */
            $affectation = $affectations[0]['affectation'];
            if (AffectationStatus::WAIT !== $affectation->getStatut()) {
                $io->error(sprintf('Le signalement %s doit être en cours. Statut : %s', $uuid, $affectation->getStatut()->value));

                continue;
            }

            $dossierRejete = new DossierStateSISHResponse($this->getDossierRejeteData($affectation, $row), 200);
            $this->esaboraManager->synchronizeAffectationFrom(
                dossierResponse: $dossierRejete,
                affectation: $affectation
            );
            $io->success(sprintf(
                'Clôture forcée effectuée : le signalement %s est clôturé pour ARS.',
                $uuid
            ));
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<string, string> $row
     *
     * @return array<string, mixed>
     */
    private function getDossierRejeteData(Affectation $affectation, array $row): array
    {
        $signalement = $affectation->getSignalement();
        $message = $row['cause du rejet'];
        $dateRejet = $row['Date rejeté'];

        return [
            'searchId' => $signalement->getUuid(),
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
                        $dateRejet,
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
