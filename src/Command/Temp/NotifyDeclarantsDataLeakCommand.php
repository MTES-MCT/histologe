<?php

namespace App\Command\Temp;

use App\Entity\Enum\SignalementStatus;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Service\InjonctionBailleur\InjonctionBailleurService;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCommand(
    name: 'app:notify-declarants-data-leak',
    description: 'Informe chaque déclarant des signalements sans e-mail occupant (créés avant le 25/09/2026) qu\'il doit prévenir les occupants d\'une fuite de données',
)]
class NotifyDeclarantsDataLeakCommand extends Command
{
    private const string CREATED_BEFORE = '2026-09-25 00:00:00';

    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly UserRepository $userRepository,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Liste les déclarants et signalements concernés sans envoyer d\'e-mail');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        /** @var array<int, array<string, mixed>> $rows */
        $rows = $this->signalementRepository->createQueryBuilder('s')
            ->select(
                's.uuid AS uuid',
                's.codeSuivi AS codeSuivi',
                's.reference AS reference',
                's.referenceInjonction AS referenceInjonction',
                's.statut AS statut',
                's.mailDeclarant AS mailDeclarant',
                's.nomDeclarant AS nomDeclarant',
                's.prenomDeclarant AS prenomDeclarant',
                's.nomOccupant AS nomOccupant',
                's.prenomOccupant AS prenomOccupant',
                'a.housenumber AS housenumber',
                'a.street AS street',
                'a.postCode AS postCode',
                'a.city AS city',
            )
            ->leftJoin('s.address', 'a')
            ->where('s.mailOccupant IS NULL OR s.mailOccupant = :empty')
            ->andWhere('s.mailDeclarant IS NOT NULL AND s.mailDeclarant != :empty')
            ->andWhere('s.createdAt < :createdBefore')
            ->andWhere('s.isImported IS NULL OR s.isImported = 0')
            ->setParameter('empty', '')
            ->setParameter('createdBefore', new \DateTimeImmutable(self::CREATED_BEFORE))
            ->orderBy('s.mailDeclarant', 'ASC')
            ->addOrderBy('s.createdAt', 'ASC')
            ->getQuery()
            ->getArrayResult();
        // TODO : statut à éliminuer ? ARCHIVE ?

        /** @var array<string, array{signalements: array<int, array<string, mixed>>}> $groups */
        $groups = [];
        foreach ($rows as $row) {
            $email = mb_strtolower(trim((string) $row['mailDeclarant']));
            $groups[$email]['signalements'][] = $row;
        }
        unset($rows);

        $io->info(\sprintf(
            '%d signalements trouvés pour %d déclarants uniques.',
            array_sum(array_map(static fn (array $group): int => \count($group['signalements']), $groups)),
            \count($groups)
        ));

        $summary = [];
        $countSent = 0;
        $countFailed = 0;

        if (!$dryRun) {
            $io->progressStart(\count($groups));
        }
        foreach ($groups as $email => $group) {
            $isAgent = null !== $this->userRepository->findAgentByEmail($email, acceptRoleApi: false);
            $firstRow = $group['signalements'][0];

            $signalementsParams = [];
            foreach ($group['signalements'] as $row) {
                $signalementsParams[] = [
                    'reference' => $this->getReference($row),
                    'adresse' => trim(($row['housenumber'] ?? '').' '.($row['street'] ?? '')),
                    'cp' => $row['postCode'] ?? '',
                    'ville' => $row['city'] ?? '',
                    'nom_occupant' => $row['nomOccupant'] ?? '',
                    'prenom_occupant' => $row['prenomOccupant'] ?? '',
                    'lien' => $isAgent
                        ? $this->generateLink('back_signalement_view', ['uuid' => $row['uuid']])
                        : $this->generateLink('front_suivi_signalement', ['code' => $row['codeSuivi']]),
                ];
            }

            $summary[] = [$email, $isAgent ? 'oui' : 'non', \count($group['signalements'])];

            if ($dryRun) {
                continue;
            }

            $sent = $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_TEMP_DATA_LEAK_DECLARANT,
                    to: $email,
                    params: [
                        'nom_declarant' => $firstRow['nomDeclarant'] ?? '',
                        'prenom_declarant' => $firstRow['prenomDeclarant'] ?? '',
                        'is_agent' => $isAgent,
                        'signalements' => $signalementsParams,
                    ],
                )
            );
            $sent ? ++$countSent : ++$countFailed;
            $io->progressAdvance();
        }

        if ($dryRun) {
            // Déclarants ayant le plus de signalements en premier
            usort($summary, static fn (array $a, array $b): int => $b[2] <=> $a[2]);
            $io->table(['E-mail déclarant', 'Agent', 'Nb signalements'], $summary);
            $io->note('Dry-run : aucun e-mail envoyé.');

            return Command::SUCCESS;
        }

        $io->progressFinish();
        $io->success(\sprintf('%d e-mails envoyés, %d en échec.', $countSent, $countFailed));

        return Command::SUCCESS;
    }

    /**
     * Reprend la logique de Signalement::getReference() sans hydrater l'entité.
     *
     * @param array<string, mixed> $row
     */
    private function getReference(array $row): ?string
    {
        $statut = $row['statut'] instanceof SignalementStatus
            ? $row['statut']
            : SignalementStatus::tryFrom((string) $row['statut']);

        if (\in_array($statut, SignalementStatus::injonctionStatuses(), true)) {
            return null !== $row['referenceInjonction']
                ? InjonctionBailleurService::REFERENCE_PREFIX.$row['referenceInjonction']
                : null;
        }

        return $row['reference'];
    }

    /**
     * @param array<string, mixed> $params
     */
    private function generateLink(string $route, array $params): string
    {
        return $this->parameterBag->get('host_url').$this->urlGenerator->generate($route, $params);
    }
}
