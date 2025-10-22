<?php

namespace App\Tests\Functional\Command;

use App\Entity\Affectation;
use App\Entity\Partner;
use App\Entity\User;
use App\Entity\UserSignalementSubscription;
use App\Repository\AffectationRepository;
use App\Repository\PartnerRepository;
use App\Repository\UserRepository;
use App\Repository\UserSignalementSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MergePartnersCommandTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?PartnerRepository $partnerRepository;
    private ?UserRepository $userRepository;
    private ?AffectationRepository $affectationRepository;
    private ?UserSignalementSubscriptionRepository $subscriptionRepository;
    private ?CommandTester $commandTester;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->partnerRepository = $this->entityManager->getRepository(Partner::class);
        $this->userRepository = $this->entityManager->getRepository(User::class);
        $this->affectationRepository = $this->entityManager->getRepository(Affectation::class);
        $this->subscriptionRepository = $this->entityManager->getRepository(UserSignalementSubscription::class);

        $application = new Application($kernel);
        $command = $application->find('app:merge-partners');
        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }

    public function testMergePartnersWithSuccess(): void
    {
        // Given: Use existing partners from fixtures
        $sourcePartner = $this->partnerRepository->findOneBy(['nom' => 'Partenaire 13-02']);
        $destinationPartner = $this->partnerRepository->findOneBy(['nom' => 'Partenaire 13-01']);

        $this->assertNotNull($sourcePartner, 'Source partner should exist in fixtures');
        $this->assertNotNull($destinationPartner, 'Destination partner should exist in fixtures');

        $sourcePartnerId = $sourcePartner->getId();
        $destinationPartnerId = $destinationPartner->getId();

        // Given: Get initial state
        $initialAffectationsCount = $sourcePartner->getAffectations()->count();
        $initialUsersCount = $sourcePartner->getUsers()->count();
        $initialDestinationAffectationsCount = $destinationPartner->getAffectations()->count();

        $this->assertGreaterThan(0, $initialAffectationsCount, 'Source partner should have affectations');
        $this->assertGreaterThan(0, $initialUsersCount, 'Source partner should have users');

        // Given: Get affectations before merge to track status preservation
        $affectationsBeforeMerge = $this->affectationRepository->findBy(['partner' => $sourcePartner]);
        $affectationIds = array_map(fn (Affectation $a) => $a->getId(), $affectationsBeforeMerge);

        // Given: Get initial subscription counts for users
        $users = $sourcePartner->getUsers();
        $initialSubscriptionCounts = [];
        foreach ($users as $user) {
            /* @var User $user */
            $initialSubscriptionCounts[$user->getId()] = $this->subscriptionRepository->count(['user' => $user]);
        }

        // When: Execute command
        $this->commandTester->execute([
            '--source-partner-id' => $sourcePartnerId,
            '--destination-partner-id' => $destinationPartnerId,
        ], ['interactive' => false]);

        // Force refresh entities
        $this->entityManager->clear();

        // Then: Check command output
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('has been archived', $output);
        $this->assertStringContainsString('Merge completed successfully', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());

        // Then: Source partner should be archived
        $sourcePartner = $this->partnerRepository->find($sourcePartnerId);
        $this->assertNotNull($sourcePartner, 'Source partner should still exist in database');
        $this->assertTrue($sourcePartner->getIsArchive(), 'Source partner should be archived');

        // Then: Destination partner should have more affectations (reload partner)
        $destinationPartner = $this->partnerRepository->find($destinationPartnerId);
        $this->assertGreaterThan(
            $initialDestinationAffectationsCount,
            $destinationPartner->getAffectations()->count(),
            'Destination partner should have more affectations after merge'
        );

        // Then: Users should be transferred to destination partner
        $this->assertGreaterThanOrEqual(
            $initialUsersCount,
            $destinationPartner->getUsers()->count(),
            'Destination partner should have at least the users from source partner'
        );

        // Then: Affectations should be transferred to destination partner
        foreach ($affectationIds as $affectationId) {
            $affectation = $this->affectationRepository->find($affectationId);
            if ($affectation) { // Some might be deleted if duplicate
                $this->assertEquals(
                    $destinationPartnerId,
                    $affectation->getPartner()->getId(),
                    'Affectation should be transferred to destination partner'
                );
            }
        }

        // Then: Source partner Users should have same subscriptions
        foreach ($initialSubscriptionCounts as $userId => $initialCount) {
            $user = $this->userRepository->find($userId);
            if ($user) {
                $finalCount = $this->subscriptionRepository->count(['user' => $user]);
                $this->assertEquals(
                    $initialCount,
                    $finalCount,
                    sprintf('User %d should have at least as many subscriptions after merge', $userId)
                );
            }
        }
    }

    public function testMergePartnersWithSameIdsFails(): void
    {
        // Given: Use existing partner from fixtures
        $partner = $this->partnerRepository->findOneBy(['nom' => 'Partenaire 13-02']);
        $this->assertNotNull($partner, 'Partner should exist in fixtures');

        $partnerId = $partner->getId();

        // When: Execute command with same IDs
        $this->commandTester->execute([
            '--source-partner-id' => $partnerId,
            '--destination-partner-id' => $partnerId,
        ], ['interactive' => false]);

        // Then: Command should fail
        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Source and destination partner IDs must be different', $output);
    }

    public function testMergePartnersWithInvalidSourceIdFails(): void
    {
        // Given: Use existing destination partner from fixtures
        $destinationPartner = $this->partnerRepository->findOneBy(['nom' => 'Partenaire 13-02']);
        $this->assertNotNull($destinationPartner, 'Destination partner should exist in fixtures');

        // When: Execute command with invalid source ID
        $this->commandTester->execute([
            '--source-partner-id' => 99999,
            '--destination-partner-id' => $destinationPartner->getId(),
        ], ['interactive' => false]);

        // Then: Command should fail
        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Source partner with ID 99999 not found', $output);

        // When: Execute command with invalid destination ID
        $this->commandTester->execute([
            '--source-partner-id' => $destinationPartner->getId(),
            '--destination-partner-id' => 99999,
        ], ['interactive' => false]);

        // Then: Command should fail
        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Destination partner with ID 99999 not found', $output);
    }
}
