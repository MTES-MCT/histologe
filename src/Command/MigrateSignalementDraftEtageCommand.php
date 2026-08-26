<?php

namespace App\Command;

use App\Entity\Enum\EtageType;
use App\Entity\Enum\SignalementDraftStatus;
use App\Repository\SignalementDraftRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-signalement-draft-etage',
    description: 'Migre le champ étage des brouillons de signalement EN_COURS vers le nouveau format RDC/DERNIER_ETAGE/SOUSSOL/AUTRE',
)]
class MigrateSignalementDraftEtageCommand extends Command
{
    private const int BATCH_SIZE = 200;

    // Aucun étage à 99 n'existe en France : sert à distinguer une migration
    // sans précision retrouvable d'une vraie saisie utilisateur.
    private const string PRECISION_INCONNUE = '99';

    public function __construct(
        private readonly SignalementDraftRepository $signalementDraftRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('apply', null, InputOption::VALUE_NONE, 'Applique réellement les modifications (sans cette option, dry-run)')
            ->addOption('uuid', null, InputOption::VALUE_REQUIRED, 'Limite la migration à un seul brouillon (pour test)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apply = (bool) $input->getOption('apply');
        $uuid = $input->getOption('uuid');

        $criteria = ['status' => SignalementDraftStatus::EN_COURS];
        if (null !== $uuid) {
            $criteria['uuid'] = $uuid;
        }
        $drafts = $this->signalementDraftRepository->findBy($criteria);

        $counts = [
            'inchange' => 0,
            'nettoye_non_appartement' => 0,
            'rdc' => 0,
            'dernier_etage' => 0,
            'soussol' => 0,
            'autre_avec_precision' => 0,
            'autre_sans_precision' => 0,
        ];
        $uuidsSentinelle = [];
        $i = 0;

        foreach ($drafts as $draft) {
            $payload = $draft->getPayload() ?? [];
            $nature = $payload['type_logement_nature'] ?? null;
            $currentEtage = $payload['adresse_logement_complement_adresse_etage'] ?? null;

            if ('appartement' !== $nature) {
                if (null === $currentEtage) {
                    ++$counts['inchange'];
                    continue;
                }
                unset($payload['adresse_logement_complement_adresse_etage']);
                $draft->setPayload($payload);
                ++$counts['nettoye_non_appartement'];
                $this->flushBatch($apply, ++$i);
                continue;
            }

            if (\in_array($currentEtage, EtageType::values(), true)) {
                ++$counts['inchange'];
                continue;
            }

            $etage = match (true) {
                'oui' === ($payload['type_logement_rdc'] ?? null) => EtageType::RDC,
                'oui' === ($payload['type_logement_dernier_etage'] ?? null) => EtageType::DERNIER_ETAGE,
                'oui' === ($payload['type_logement_sous_sol_sans_fenetre'] ?? null) => EtageType::SOUSSOL,
                default => EtageType::AUTRE,
            };
            $payload['adresse_logement_complement_adresse_etage'] = $etage->value;

            if (EtageType::AUTRE === $etage) {
                $precision = null;
                if (null !== $currentEtage && preg_match('/\d{1,2}/', (string) $currentEtage, $matches)) {
                    $precision = $matches[0];
                }
                if (null === $precision) {
                    $precision = self::PRECISION_INCONNUE;
                    $uuidsSentinelle[] = $draft->getUuid();
                    ++$counts['autre_sans_precision'];
                } else {
                    ++$counts['autre_avec_precision'];
                }
                $payload['adresse_logement_complement_adresse_etage_precision'] = $precision;
            } else {
                ++$counts[match ($etage) {
                    EtageType::RDC => 'rdc',
                    EtageType::DERNIER_ETAGE => 'dernier_etage',
                    EtageType::SOUSSOL => 'soussol',
                }];
            }

            $draft->setPayload($payload);
            $this->flushBatch($apply, ++$i);
        }

        if ($apply) {
            $this->entityManager->flush();
        }

        $io->table(['Catégorie', 'Nombre'], [
            ['Déjà au bon format / inchangé', $counts['inchange']],
            ['Nettoyé (nature non appartement)', $counts['nettoye_non_appartement']],
            ['Migré → RDC', $counts['rdc']],
            ['Migré → DERNIER_ETAGE', $counts['dernier_etage']],
            ['Migré → SOUSSOL', $counts['soussol']],
            ['Migré → AUTRE (précision retrouvée)', $counts['autre_avec_precision']],
            ['Migré → AUTRE (précision inconnue, sentinelle "'.self::PRECISION_INCONNUE.'")', $counts['autre_sans_precision']],
        ]);

        if ([] !== $uuidsSentinelle) {
            $io->warning(\sprintf(
                '%d brouillon(s) migré(s) avec une précision d\'étage inconnue (valeur sentinelle "%s"). UUIDs (20 premiers) : %s',
                \count($uuidsSentinelle),
                self::PRECISION_INCONNUE,
                implode(', ', \array_slice($uuidsSentinelle, 0, 20))
            ));
        }

        if (!$apply) {
            $io->note('Dry-run : aucune modification enregistrée. Relancez avec --apply pour appliquer.');
        } else {
            $io->success('Migration appliquée.');
        }

        return Command::SUCCESS;
    }

    private function flushBatch(bool $apply, int $count): void
    {
        if ($apply && 0 === $count % self::BATCH_SIZE) {
            $this->entityManager->flush();
        }
    }
}
