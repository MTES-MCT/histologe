<?php

namespace App\Command;

use App\Entity\Affectation;
use App\Entity\Partner;
use App\Repository\PartnerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:merge-partners',
    description: 'Merge two partners: transfer signalements, users and subscriptions from source partner to destination partner',
)]
class MergePartnersCommand extends Command
{
    public function __construct(
        private readonly PartnerRepository $partnerRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('source-partner-id', null, InputOption::VALUE_REQUIRED, 'ID of the partner to merge (will be archived)')
            ->addOption('destination-partner-id', null, InputOption::VALUE_REQUIRED, 'ID of the destination partner');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $sourcePartnerId = (int) $input->getOption('source-partner-id');
        $destinationPartnerId = (int) $input->getOption('destination-partner-id');

        if ($sourcePartnerId === $destinationPartnerId) {
            $io->error('Source and destination partner IDs must be different.');

            return Command::FAILURE;
        }

        $sourcePartner = $this->partnerRepository->find($sourcePartnerId);
        if (!$sourcePartner) {
            $io->error(sprintf('Source partner with ID %d not found.', $sourcePartnerId));

            return Command::FAILURE;
        }

        $destinationPartner = $this->partnerRepository->find($destinationPartnerId);
        if (!$destinationPartner) {
            $io->error(sprintf('Destination partner with ID %d not found.', $destinationPartnerId));

            return Command::FAILURE;
        }

        if ($sourcePartner->getTerritory() !== $destinationPartner->getTerritory()) {
            $io->error('Source and destination partners belong to different territories.');

            return Command::FAILURE;
        }

        $io->section('Partners Information');
        $io->table(
            ['Property', 'Source Partner (to be archived)', 'Destination Partner'],
            [
                ['ID', $sourcePartner->getId(), $destinationPartner->getId()],
                ['Name', $sourcePartner->getNom(), $destinationPartner->getNom()],
            ]
        );

        $affectationStatusChanges = $this->previewAffectationStatusChanges($sourcePartner, $destinationPartner);
        if (!empty($affectationStatusChanges)) {
            $io->section('Affectation Status Changes Preview');
            $io->table(
                ['Change Type', 'Count', 'Details'],
                $affectationStatusChanges
            );
        }

        if (!$io->confirm('Do you want to proceed with the merge?', true)) {
            $io->info('Operation cancelled.');

            return Command::SUCCESS;
        }

        $io->section('Starting merge process...');

        try {
            $affectationsCount = $this->transferAffectations($sourcePartner, $destinationPartner, $io);
            $io->success(sprintf('Transferred %d affectation(s). %d duplicate affectations removed.', $affectationsCount['transferred'], $affectationsCount['removed_duplicates']));

            $usersCount = $this->transferUsers($sourcePartner, $destinationPartner, $io);
            $io->success(sprintf('Transferred %d user(s).', $usersCount));

            $sourcePartner->setIsArchive(true);
            $this->entityManager->flush();
            $io->success(sprintf('Partner "%s" (ID: %d) has been archived.', $sourcePartner->getNom(), $sourcePartner->getId()));

            $io->success('Merge completed successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $io->error('An error occurred during the merge process: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /** @return array<int> */
    private function transferAffectations(Partner $sourcePartner, Partner $destinationPartner, SymfonyStyle $io): array
    {
        $affectations = $sourcePartner->getAffectations();
        $count = [
            'transferred' => 0,
            'removed_duplicates' => 0,
        ];

        foreach ($affectations as $affectation) {
            /** @var Affectation $affectation */
            // Check if destination partner already has an affectation for this signalement
            $existingAffectation = $this->entityManager->getRepository(Affectation::class)
                ->findOneBy([
                    'signalement' => $affectation->getSignalement(),
                    'partner' => $destinationPartner,
                ]);

            if ($existingAffectation) {
                $io->warning(sprintf(
                    'Affectation already exists for signalement %s and destination partner. Removing the duplicate affectation.',
                    $affectation->getSignalement()->getReference()
                ));
                // Remove the duplicate affectation from source partner
                $this->entityManager->remove($affectation);
                ++$count['removed_duplicates'];
            } else {
                // Transfer affectation to destination partner
                $affectation->setPartner($destinationPartner);
                ++$count['transferred'];
            }
        }

        return $count;
    }

    private function transferUsers(Partner $sourcePartner, Partner $destinationPartner, SymfonyStyle $io): int
    {
        $count = 0;
        $userPartners = $sourcePartner->getUserPartners();

        foreach ($userPartners as $userPartner) {
            $user = $userPartner->getUser();

            // Check if user is already in destination partner
            if (!$user->hasPartner($destinationPartner)) {
                // Transfer user to destination partner
                $userPartner->setPartner($destinationPartner);
                $io->info(sprintf(
                    'User "%s" (ID: %d) transferred from %s to %s.',
                    $user->getEmail(),
                    $user->getId(),
                    $sourcePartner->getNom(),
                    $destinationPartner->getNom(),
                ));
                ++$count;
            } else {
                $io->warning(sprintf(
                    'User "%s" (ID: %d) is already in destination partner. Removing useless relationship.',
                    $user->getEmail(),
                    $user->getId()
                ));
                // Remove the duplicate user-partner relationship
                $this->entityManager->remove($userPartner);
            }
        }

        return $count;
    }

    /**
     * Preview affectation status changes that will occur during merge.
     *
     * @return array<int, array<string>>
     */
    private function previewAffectationStatusChanges(Partner $sourcePartner, Partner $destinationPartner): array
    {
        $changes = [];
        $duplicatesToRemove = 0;
        $duplicatesWithDifferentStatus = [];

        foreach ($sourcePartner->getAffectations() as $affectation) {
            /** @var Affectation $affectation */
            $existingAffectation = $this->entityManager->getRepository(Affectation::class)
                ->findOneBy([
                    'signalement' => $affectation->getSignalement(),
                    'partner' => $destinationPartner,
                ]);

            if ($existingAffectation) {
                ++$duplicatesToRemove;

                // Check if statuses are different
                if ($affectation->getStatut() !== $existingAffectation->getStatut()) {
                    $duplicatesWithDifferentStatus[] = [
                        'reference' => $affectation->getSignalement()->getReference(),
                        'sourceStatus' => $affectation->getStatut()->label(),
                        'destinationStatus' => $existingAffectation->getStatut()->label(),
                    ];
                }
            }
        }

        $totalAffectations = $sourcePartner->getAffectations()->count();
        $affectationsToTransfer = $totalAffectations - $duplicatesToRemove;

        if ($affectationsToTransfer > 0) {
            $changes[] = [
                'Transfer',
                $affectationsToTransfer,
                sprintf('%d affectation(s) will be transferred to destination partner', $affectationsToTransfer),
            ];
        }

        if ($duplicatesToRemove > 0) {
            $duplicatesWithSameStatus = $duplicatesToRemove - \count($duplicatesWithDifferentStatus);
            $changes[] = [
                'Duplicate removal',
                $duplicatesToRemove,
                sprintf('%d duplicate affectation(s) will be removed (already exist in destination)', $duplicatesToRemove),
            ];

            // Add details for duplicates with same status
            if ($duplicatesWithSameStatus > 0) {
                $changes[] = [
                    '  ↳ Same status',
                    $duplicatesWithSameStatus,
                    sprintf('%d affectation(s) with identical status will be removed', $duplicatesWithSameStatus),
                ];
            }

            // Add details for duplicates with different statuses
            if (!empty($duplicatesWithDifferentStatus)) {
                $changes[] = [
                    '  ↳ Status conflicts',
                    \count($duplicatesWithDifferentStatus),
                    sprintf('%d affectation(s) with different status:', \count($duplicatesWithDifferentStatus)),
                ];

                foreach ($duplicatesWithDifferentStatus as $duplicate) {
                    $changes[] = [
                        '     •',
                        '',
                        sprintf(
                            'Signalement %s: source "%s" removed, destination "%s" kept',
                            $duplicate['reference'],
                            $duplicate['sourceStatus'],
                            $duplicate['destinationStatus']
                        ),
                    ];
                }
            }
        }

        if (empty($changes)) {
            $changes[] = [
                'No changes',
                0,
                'No affectations to transfer',
            ];
        }

        return $changes;
    }
}
