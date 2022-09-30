<?php

namespace App\Command;

use App\Entity\Affectation;
use App\Entity\Notification;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\Territory;
use App\Entity\User;
use App\Manager\SignalementManager;
use App\Manager\TerritoryManager;
use App\Manager\UserManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-notification-legacy',
    description: 'Get Notification from legacy',
)]
class FixNotificationLegacyCommand extends Command
{
    public const LEGACY_TERRITORY = [
        '81', '08', '29', '69', '71', '63', '47', '19', '2A', '31', '59', '64', '04', '06', '13',
    ];

    private Connection|null $connection;
    private Territory $territory;
    private SymfonyStyle $io;
    private int $nbNotificationSignalement = 0;
    private int $nbNotificationSuivi = 0;
    private int $nbNotificationAffectation = 0;

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private TerritoryManager $territoryManager,
        private UserManager $userManager,
        private SignalementManager $signalementManager,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('territory_zip', InputArgument::REQUIRED, 'Territory code department');
        $this->addOption('days', null, InputArgument::OPTIONAL, 'Get only the last x days notification');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $territoryZip = $input->getArgument('territory_zip');
        if (!\in_array($territoryZip, self::LEGACY_TERRITORY)) {
            $this->io->error(sprintf('%s is not legacy territory', $territoryZip));

            return Command::FAILURE;
        }

        $clauseWhere = '';
        if (null !== $input->getOption('days')) {
            $days = (int) $input->getOption('days');
            $clauseWhere = "WHERE DATE(n.created_at) >= (DATE(NOW()) - INTERVAL $days DAY)";
        }

        $this->io->info(sprintf('You passed an argument: %s', $territoryZip));
        $this->territory = $this->territoryManager->findOneBy(['zip' => $territoryZip]);

        /* @var Connection $connection */
        $this->connection = $this->managerRegistry->getConnection('legacy_'.$territoryZip);
        $this->connection->connect();

        /** @var Statement $statement */
        $selectNotificationSQL = <<<SQL
                    SELECT n.id, u.email, s.uuid, n.suivi_id, n.affectation_id, n.is_seen, n.type, n.created_at
                    FROM notification n
                    INNER JOIN user u on u.id = n.user_id
                    INNER JOIN signalement s on s.id = n.signalement_id
                    LEFT JOIN suivi su on su.id = n.suivi_id
                    LEFT JOIN affectation a on a.id = n.affectation_id
                    $clauseWhere
                    ORDER BY n.id;
        SQL;

        $statement = $this->connection->prepare($selectNotificationSQL);
        $legacyNotifications = $statement->executeQuery()->fetchAllAssociative();

        $progressBar = new ProgressBar($output, \count($legacyNotifications));
        $progressBar->start();

        $nbNotificationSuivi = 0;
        foreach ($legacyNotifications as $legacyNotification) {
            /** @var User $user */
            $user = $this->userManager->findOneBy(['email' => $legacyNotification['email']]);

            /** @var Signalement $signalement */
            $signalement = $this->signalementManager->findOneBy([
                'uuid' => $legacyNotification['uuid'],
                'territory' => $this->territory,
            ]);

            $suivi = $this->findSuivi($signalement, $legacyNotification);

            $affectation = $this->findAffectation($signalement, $user->getPartner(), $legacyNotification);

            if (null === $suivi && null === $affectation) {
                ++$this->nbNotificationSignalement;
            }

            $notification = (new Notification())
                ->setSignalement($signalement)
                ->setUser($user)
                ->setCreatedAt(new \DateTimeImmutable($legacyNotification['created_at']))
                ->setIsSeen($legacyNotification['is_seen'])
                ->setType($legacyNotification['type'])
                ->setAffectation($affectation)
                ->setSuivi($suivi);

            $this->entityManager->persist($notification);
            $progressBar->advance();
        }

        $this->entityManager->flush();
        $progressBar->finish();

        $this->io->newLine();
        $this->io->success(sprintf('%s notifications added', \count($legacyNotifications)));
        $this->io->note(sprintf('including %s signalement notifications', $this->nbNotificationSignalement));
        $this->io->note(sprintf('including %s suivi notifications', $this->nbNotificationSuivi));
        $this->io->note(sprintf('including %s affectation notifications', $this->nbNotificationAffectation));

        return Command::SUCCESS;
    }

    private function findSuivi(Signalement $signalement, array $legacyNotification): ?Suivi
    {
        if (empty($legacyNotification['suivi_id'])) {
            return null;
        }

        $selectSuiviLegacySQL = <<<SQL
                SELECT s.id, u.email, s.created_at, s.description, s.is_public
                FROM suivi s
                LEFT JOIN user u on u.id = s.created_by_id
                where s.id = :suivi_id;
         SQL;

        $statement = $this->connection->prepare($selectSuiviLegacySQL);
        $legacySuivi = $statement->executeQuery(['suivi_id' => (int) $legacyNotification['suivi_id']])->fetchAssociative();
        ++$this->nbNotificationSuivi;

        return $this->entityManager->getRepository(Suivi::class)->findOneBy([
             'signalement' => $signalement,
             'description' => $legacySuivi['description'],
             'createdAt' => new \DateTimeImmutable($legacySuivi['created_at']),
             'isPublic' => (int) $legacySuivi['is_public'],
        ]);
    }

    private function findAffectation(Signalement $signalement, Partner $partner, array $legacyNotification): ?Affectation
    {
        if (empty($legacyNotification['affectation_id'])) {
            return null;
        }

        $selectAffectationLegacySQL = <<<SQL
            SELECT s.uuid, p.nom, a.answered_at, a.created_at, a.statut,
                   u1.email as answered_by, u2.email as affected_by, a.motif_cloture
            FROM affectation a
            INNER JOIN signalement s on s.id = a.signalement_id
            INNER JOIN partenaire p on p.id = a.partenaire_id
            LEFT JOIN user u1 on u1.id = a.answered_by_id
            INNER JOIN user u2 on u2.id = a.affected_by_id
            WHERE a.id = {$legacyNotification['affectation_id']};
            SQL;

        $statement = $this->connection->prepare($selectAffectationLegacySQL);
        $legacyAffectation = $statement->executeQuery()->fetchAssociative();
        $answeredBy = $this->userManager->findOneBy(['email' => $legacyAffectation['answered_by']]);
        $affectedBy = $this->userManager->findOneBy(['email' => $legacyAffectation['affected_by']]);

        ++$this->nbNotificationAffectation;

        return $this->entityManager->getRepository(Affectation::class)->findOneBy([
            'signalement' => $signalement,
            'partner' => $partner,
            'statut' => $legacyAffectation['statut'],
            'answeredBy' => $answeredBy,
            'affectedBy' => $affectedBy,
            'motifCloture' => $legacyAffectation['motif_cloture'],
            'createdAt' => new \DateTimeImmutable($legacyAffectation['created_at']),
        ]);
    }
}
