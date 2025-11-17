<?php

namespace App\Command;

use App\Manager\HistoryEntryManager;
use App\Manager\UserManager;
use App\Manager\UserSignalementSubscriptionManager;
use App\Repository\AffectationRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-rt-legacy-subscriptions-when-partner-affected',
    description: 'Initialize user signalement subscriptions for existing signalements and affectations',
)]
class FixRtLegacySubscriptionsWhenPartnerAffectedCommand
{
    private const int BATCH_SIZE = 1000;
    private ?\DateTimeImmutable $activationDate = null;
    /** @var array<int, array<int>> */
    private array $usersByPartner = [];

    public function __construct(
        private readonly UserManager $userManager,
        private readonly UserRepository $userRepository,
        private readonly AffectationRepository $affectationRepository,
        private readonly SignalementRepository $signalementRepository,
        private readonly UserSignalementSubscriptionManager $userSignalementSubscriptionManager,
        private readonly EntityManagerInterface $entityManager,
        private readonly HistoryEntryManager $historyEntryManager,
    ) {
        $this->activationDate = new \DateTimeImmutable('2025-10-28 12:25:00');
        $this->historyEntryManager->removeEntityListeners();
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $creationCounter = 0;
        $userSystem = $this->userManager->getSystemUser();
        $listAgents = $this->userRepository->findAllUnarchivedRT();
        foreach ($listAgents as $user) {
            if (!isset($this->usersByPartner[$user['partner_id']])) {
                $this->usersByPartner[$user['partner_id']] = [];
            }
            $this->usersByPartner[$user['partner_id']][] = $user['id'];
        }
        $affectations = $this->affectationRepository->findAllActiveAffectationOnPartnerWithRt($this->activationDate);
        $io->info(count($affectations).' affectations à traiter.');

        $progressBar = $io->createProgressBar(count($affectations));
        $i = 0;
        foreach ($affectations as $affectation) {
            if (!isset($this->usersByPartner[$affectation['partner_id']])) {
                continue;
            }
            foreach ($this->usersByPartner[$affectation['partner_id']] as $userId) {
                $user = $this->userRepository->find($userId);
                $signalement = $this->signalementRepository->find($affectation['signalement_id']);
                $created = false;
                $sub = $this->userSignalementSubscriptionManager->createOrGet(
                    userToSubscribe: $user,
                    signalement: $signalement,
                    createdBy: $userSystem,
                    subscriptionCreated: $created
                );
                if ($created) {
                    $sub->setIsLegacy(true);
                    ++$creationCounter;
                }
            }
            ++$i;
            if (($i % self::BATCH_SIZE) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $userSystem = $this->userManager->getSystemUser();
            }
            $progressBar->advance();
        }
        $this->entityManager->flush();
        $progressBar->finish();
        $io->newLine();
        $io->success($creationCounter.' abonnements RT créés.');

        return Command::SUCCESS;
    }
}
