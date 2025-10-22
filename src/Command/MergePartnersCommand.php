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

        // Validation: IDs must be different
        if ($sourcePartnerId === $destinationPartnerId) {
            $io->error('Source and destination partner IDs must be different.');

            return Command::FAILURE;
        }

        // Load partners
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

        // Display confirmation
        $io->section('Partners Information');
        $io->table(
            ['Property', 'Source Partner (to be archived)', 'Destination Partner'],
            [
                ['ID', $sourcePartner->getId(), $destinationPartner->getId()],
                ['Name', $sourcePartner->getNom(), $destinationPartner->getNom()],
                ['Territory', $sourcePartner->getTerritory()?->getName() ?? 'N/A', $destinationPartner->getTerritory()?->getName() ?? 'N/A'],
            ]
        );

        if (!$io->confirm('Do you want to proceed with the merge?', true)) {
            $io->info('Operation cancelled.');

            return Command::SUCCESS;
        }

        $io->section('Starting merge process...');

        try {
            $this->entityManager->beginTransaction();

            // Transfer all affectations from source to destination partner
            $affectationsCount = $this->transferAffectations($sourcePartner, $destinationPartner, $io);
            $io->success(sprintf('Transferred %d affectation(s).', $affectationsCount));

            // Transfer users from source to destination partner
            $usersCount = $this->transferUsers($sourcePartner, $destinationPartner, $io);
            $io->success(sprintf('Transferred %d user(s).', $usersCount));

            // Archive source partner (logical deletion)
            $sourcePartner->setIsArchive(true);
            $this->entityManager->flush();
            $io->success(sprintf('Partner "%s" (ID: %d) has been archived.', $sourcePartner->getNom(), $sourcePartner->getId()));

            $this->entityManager->commit();

            $io->success('Merge completed successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $io->error('An error occurred during the merge process: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    private function transferAffectations(Partner $sourcePartner, Partner $destinationPartner, SymfonyStyle $io): int
    {
        $affectations = $sourcePartner->getAffectations();
        $count = 0;

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
                    'Affectation already exists for signalement #%d and destination partner. Removing the duplicate affectation.',
                    $affectation->getSignalement()->getId()
                ));
                // Remove the duplicate affectation from source partner
                $this->entityManager->remove($affectation);
            } else {
                // Transfer affectation to destination partner
                $affectation->setPartner($destinationPartner);
                ++$count;
            }
        }

        $this->entityManager->flush();

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
                ++$count;
            } else {
                $io->warning(sprintf(
                    'User "%s" (ID: %d) is already in destination partner. Removing duplicate.',
                    $user->getEmail(),
                    $user->getId()
                ));
                // Remove the duplicate user-partner relationship
                $this->entityManager->remove($userPartner);
            }
        }

        $this->entityManager->flush();

        return $count;
    }
}
