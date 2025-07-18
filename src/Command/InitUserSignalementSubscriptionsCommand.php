<?php

namespace App\Command;

use App\Entity\User;
use App\Manager\HistoryEntryManager;
use App\Repository\AffectationRepository;
use App\Repository\SuiviRepository;
use App\Repository\UserRepository;
use App\Repository\UserSignalementSubscriptionRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:init-user-signalement-subscriptions',
    description: 'Initialize user signalement subscriptions for existing signalements and affectations',
)]
class InitUserSignalementSubscriptionsCommand
{
    private const int BATCH_SIZE = 50000;
    /** @var array<int, array<int>> */
    private array $usersByPartner = [];
    /** @var array<string, string> */
    private array $affectationSignalement = [];
    /** @var array<string, true> */
    private array $existingSubscriptions = [];
    private User $userAdmin;
    private Connection $connection;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SuiviRepository $suiviRepository,
        private readonly AffectationRepository $affectationRepository,
        private readonly UserRepository $userRepository,
        private readonly UserSignalementSubscriptionRepository $userSignalementSubscriptionRepository,
        private readonly HistoryEntryManager $historyEntryManager,
        #[Autowire(env: 'USER_SYSTEM_EMAIL')]
        private readonly string $userSystemEmail,
    ) {
        $this->historyEntryManager->removeEntityListeners();
        $this->userAdmin = $this->userRepository->findOneBy(['email' => $this->userSystemEmail]);
        $this->connection = $this->entityManager->getConnection();
    }

    public function __invoke(SymfonyStyle $io): int
    {
        // chargement des abonnements existants
        $subs = $this->userSignalementSubscriptionRepository->findAll();
        foreach ($subs as $sub) {
            $this->existingSubscriptions[$sub->getSignalement()->getId().'-'.$sub->getUser()->getId()] = true;
        }
        // chargement des agents par partenaire
        $listAgents = $this->userRepository->findAllUnarchivedUserPartnerOrAdminPartner();
        foreach ($listAgents as $user) {
            if (!isset($this->usersByPartner[$user['partner_id']])) {
                $this->usersByPartner[$user['partner_id']] = [];
            }
            $this->usersByPartner[$user['partner_id']][] = $user['id'];
        }
        // gestions des abonnements RT
        $suivis = $this->suiviRepository->findWithUnarchivedRtDistinctByUserAndSignalement();
        $io->info(count($suivis).' abonnements RT à traiter.');
        $total = 0;
        $progressBar = $io->createProgressBar(count($suivis));
        $progressBar->start();
        $i = 0;
        $batchData = [];
        foreach ($suivis as $suivi) {
            if (isset($this->existingSubscriptions[$suivi['signalement_id'].'-'.$suivi['id']])) {
                continue;
            }
            $batchData[] = ['user_id' => $suivi['id'], 'signalement_id' => $suivi['signalement_id'], 'created_at' => $suivi['created_at']];
            ++$i;
            if (($i % self::BATCH_SIZE) === 0) {
                $this->insertBatch($batchData);
                $batchData = [];
            }
            $progressBar->advance();
        }
        $this->insertBatch($batchData);
        $total += $i;
        $progressBar->finish();
        $io->newLine();
        // gestions des abonnements agents
        $affectations = $this->affectationRepository->findAllActiveAffectationsOnActiveSignalements();
        $io->info(count($affectations).' affectations à traiter.');

        $progressBar = $io->createProgressBar(count($affectations));
        $progressBar->start();
        $i = 0;
        $batchData = [];
        foreach ($affectations as $affectation) {
            $key = $affectation['signalement_id'].'-'.$affectation['partner_id'];
            if (isset($this->affectationSignalement[$key])) {
                // quelques affectations existent en doublons pour un même partenaire et signalement on en traite qu'une seule
                continue;
            }
            $this->affectationSignalement[$key] = $key;
            if (!isset($this->usersByPartner[$affectation['partner_id']])) {
                continue;
            }
            foreach ($this->usersByPartner[$affectation['partner_id']] as $userId) {
                if (isset($this->existingSubscriptions[$affectation['signalement_id'].'-'.$userId])) {
                    continue;
                }
                $batchData[] = ['user_id' => $userId, 'signalement_id' => $affectation['signalement_id'], 'created_at' => $affectation['answered_at']];
                ++$i;
                if (($i % self::BATCH_SIZE) === 0) {
                    $this->insertBatch($batchData);
                    $batchData = [];
                }
            }
            $progressBar->advance();
        }
        $this->insertBatch($batchData);
        $total += $i;
        $progressBar->finish();
        $io->newLine(2);
        $io->success($total.' Abonements ajoutés.');

        return Command::SUCCESS;
    }

    /**
     * @param array<array{user_id: int, signalement_id: int, created_at: string}> $batchData
     */
    private function insertBatch(array $batchData): void
    {
        $sql = 'INSERT INTO user_signalement_subscription (user_id, signalement_id, created_by_id, is_legacy, created_at) VALUES ';
        $values = [];
        $parameters = [];

        foreach ($batchData as $data) {
            $values[] = '(?, ?, ?, ?, ?)';
            $parameters[] = $data['user_id'];
            $parameters[] = $data['signalement_id'];
            $parameters[] = $this->userAdmin->getId();
            $parameters[] = 1;
            $parameters[] = $data['created_at'] ?? date('Y-m-d H:i:s');
        }

        $sql .= implode(', ', $values);
        $this->connection->executeStatement($sql, $parameters);
    }
}
