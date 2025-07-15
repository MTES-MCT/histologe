<?php

namespace App\Command;

use App\Entity\User;
use App\Manager\HistoryEntryManager;
use App\Repository\AffectationRepository;
use App\Repository\SuiviRepository;
use App\Repository\UserRepository;
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
    private array $usersByPartner = [];
    private array $affectationSignalement = [];
    private User $userAdmin;
    private Connection $connection;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SuiviRepository $suiviRepository,
        private readonly AffectationRepository $affectationRepository,
        private readonly UserRepository $userRepository,
        private readonly HistoryEntryManager $historyEntryManager,
        #[Autowire(env: 'USER_SYSTEM_EMAIL')]
        private readonly string $userSystemEmail,
    ) {
        $this->historyEntryManager->removeEntityListeners();
        $this->userAdmin = $this->userRepository->findOneBy(['email' => $this->userSystemEmail]);
        $this->connection = $this->entityManager->getConnection();
        $listAgents = $this->userRepository->findAllUnarchivedUserPartnerOrAdminPartner();
        foreach ($listAgents as $user) {
            if (!isset($this->usersByPartner[$user['partner_id']])) {
                $this->usersByPartner[$user['partner_id']] = [];
            }
            $this->usersByPartner[$user['partner_id']][] = $user['id'];
        }
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $suivis = $this->suiviRepository->findWithUnarchivedRtDistinctByUserAndSignalement();
        $io->info(count($suivis).' abonnements RT à traiter.');
        $total = 0;
        $progressBar = $io->createProgressBar(count($suivis));
        $progressBar->start();
        $i = 0;
        $batchData = [];
        foreach ($suivis as $suivi) {
            $batchData[] = ['user_id' => $suivi['id'], 'signalement_id' => $suivi['signalement_id']];
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
                $batchData[] = ['user_id' => $userId, 'signalement_id' => $affectation['signalement_id']];
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

    private function insertBatch(array $batchData): void
    {
        $sql = 'INSERT INTO user_signalement_subscription (user_id, signalement_id, created_by_id, is_legacy, created_at) VALUES ';
        $values = [];
        $parameters = [];
        $date = date('Y-m-d H:i:s');

        foreach ($batchData as $data) {
            $values[] = '(?, ?, ?, ?, ?)';
            $parameters[] = $data['user_id'];
            $parameters[] = $data['signalement_id'];
            $parameters[] = $this->userAdmin->getId();
            $parameters[] = 1;
            $parameters[] = $date;
        }

        $sql .= implode(', ', $values);
        $this->connection->executeStatement($sql, $parameters);
    }
}
