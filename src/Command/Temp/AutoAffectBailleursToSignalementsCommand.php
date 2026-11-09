<?php

namespace App\Command\Temp;

use App\Entity\Affectation;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Manager\AffectationManager;
use App\Manager\UserManager;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:auto-affect-bailleurs-to-signalements',
    description: 'Affectation automatique des bailleurs aux signalements',
)]
class AutoAffectBailleursToSignalementsCommand extends Command
{
    public const START_DATE = '2026-01-01 00:00:00';

    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly PartnerRepository $partnerRepository,
        private readonly TerritoryRepository $territoryRepository,
        private readonly AffectationManager $affectationManager,
        private readonly UserManager $userManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'territory_zip',
            InputArgument::REQUIRED,
            'Territory zip to target'
        )
        ->addOption(
            '--dry-run',
            null,
            InputOption::VALUE_NONE,
            'If set, the command will not persist changes to the database'
        )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $territoryZip = $input->getArgument('territory_zip');
        $territory = $this->territoryRepository->findOneBy([
            'zip' => $territoryZip,
            'isActive' => true,
        ]);
        if (!$territory) {
            $output->writeln(sprintf('<error>Le territoire avec le code postal "%s" n\'existe pas ou n\'est pas actif.</error>', $territoryZip));

            return Command::FAILURE;
        }

        $dryRun = $input->getOption('dry-run');

        $adminUser = $this->userManager->getSystemUser();

        $io = new SymfonyStyle($input, $output);

        $queryBuilder = $this->signalementRepository->createQueryBuilder('s');
        $queryBuilder
            ->where('s.createdAt >= :dateStart')
            ->andWhere('s.territory = :territory')
            ->andWhere('s.bailleur IS NOT NULL')
            ->andWhere('s.isLogementSocial = true or s.isLogementSocial IS NULL')
            ->andWhere('s.statut IN (:statuses)')
            ->setParameter('dateStart', self::START_DATE)
            ->setParameter('territory', $territory)
            ->setParameter('statuses', [SignalementStatus::ACTIVE]);

        /** @var Signalement[] $signalements */
        $signalements = $queryBuilder->getQuery()->getResult();

        $io->info(sprintf('%d signalements à affecter.', count($signalements)));

        $affectedSignalements = [];
        $notAffectedSignalements = [];
        $alreadyAffectedSignalements = [];
        $io->progressStart(count($signalements));
        foreach ($signalements as $signalement) {
            $io->progressAdvance();

            $bailleur = $signalement->getBailleur();

            $partner = $this->partnerRepository->findOneBy([
                'bailleur' => $bailleur,
                'territory' => $territory,
                'isArchive' => false,
                'type' => PartnerType::BAILLEUR_SOCIAL,
            ]);

            if (!$partner) {
                $notAffectedSignalements[] = $signalement;
                continue;
            }

            $newAffectation = null;
            if ($dryRun) {
                // En dry-run, simulation de createAffectationFrom sans le flush
                $hasAffectation = $signalement
                    ->getAffectations()
                    ->exists(
                        static function (int $key, Affectation $affectation) use ($partner) {
                            return $affectation->getPartner() === $partner;
                        }
                    );

                if (!$hasAffectation) {
                    $newAffectation = (new Affectation())
                        ->setSignalement($signalement)
                        ->setPartner($partner)
                        ->setAffectedBy($user ?? null)
                        ->setTerritory($signalement->getAddress()->getTerritory());
                }
            } else {
                $newAffectation = $this->affectationManager->createAffectationFrom(
                    signalement: $signalement,
                    partner: $partner,
                    user: $adminUser,
                    dispatchEvent: false, // On évite d'envoyer plein de mails d'un coup à chaque bailleur
                    dispatchInterconnection: false, // Pas besoin de gérer les interconnexions pour les bailleurs sociaux
                );
            }

            if ($newAffectation) {
                $affectedSignalements[] = [
                    'id' => $signalement->getId(),
                    'uuid' => $signalement->getUuid(),
                    'reference' => $signalement->getReference(),
                    'bailleur' => $bailleur->getName(),
                    'partner' => $partner->getNom(),
                ];
            } else {
                $alreadyAffectedSignalements[] = $signalement;
            }
        }
        $io->progressFinish();

        if (!empty($affectedSignalements)) {
            $io->section('Tableau récapitulatif des signalements affectés');
            $io->table(
                ['id', 'uuid', 'reference', 'bailleur', 'partner'],
                $affectedSignalements
            );
        }

        if (!empty($notAffectedSignalements)) {
            $io->section('Tableau récapitulatif des signalements non affectés');
            $io->table(
                ['id', 'uuid', 'reference', 'bailleur'],
                array_map(static fn (Signalement $s) => [
                    'id' => $s->getId(),
                    'uuid' => $s->getUuid(),
                    'reference' => $s->getReference(),
                    'bailleur' => $s->getBailleur()->getName(),
                ], $notAffectedSignalements)
            );
        }

        if (!empty($alreadyAffectedSignalements)) {
            $io->section('Tableau récapitulatif des signalements déjà affectés précédemment');
            $io->table(
                ['id', 'uuid', 'reference', 'bailleur'],
                array_map(static fn (Signalement $s) => [
                    'id' => $s->getId(),
                    'uuid' => $s->getUuid(),
                    'reference' => $s->getReference(),
                    'bailleur' => $s->getBailleur()->getName(),
                ], $alreadyAffectedSignalements)
            );
        }

        $io->success(sprintf('%d signalements affectés.', count($affectedSignalements)));

        return Command::SUCCESS;
    }
}
