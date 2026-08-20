<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SuiviRepositoryTest extends KernelTestCase
{
    private SuiviRepository $suiviRepository;

    private EntityManagerInterface $entityManager;

    public const USER_ADMIN = 'admin-01@signal-logement.fr';

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
        $this->suiviRepository = $this->entityManager->getRepository(Suivi::class);
    }

    public function testFindSuiviByDescription(): void
    {
        $signalement = $this->getSignalementByReference('2023-15');

        $result = $this->suiviRepository->findSuiviByDescription($signalement, 'premier suivi de partenaire');
        $this->assertCount(1, $result);
        $this->assertStringContainsString('Ceci est le premier suivi de partenaire 13-01', $result[0]->getDescription());
    }

    public function testFindSuiviByDescriptionMatchesMultipleResults(): void
    {
        $signalement = $this->getSignalementByReference('2023-15');

        $result = $this->suiviRepository->findSuiviByDescription($signalement, 'suivi de partenaire 13-01');
        $this->assertCount(2, $result);
    }

    public function testFindSuiviByDescriptionFiltersByCategory(): void
    {
        $signalement = $this->getSignalementByReference('2023-15');

        $result = $this->suiviRepository->findSuiviByDescription($signalement, 'suivi de partenaire 13-01', SuiviCategory::MESSAGE_PARTNER);
        $this->assertCount(2, $result);

        $result = $this->suiviRepository->findSuiviByDescription($signalement, 'suivi de partenaire 13-01', SuiviCategory::MESSAGE_USAGER);
        $this->assertCount(0, $result);
    }

    public function testFindSuiviByDescriptionReturnsEmptyForOtherSignalement(): void
    {
        $signalement = $this->getSignalementByReference('2022-3');

        $result = $this->suiviRepository->findSuiviByDescription($signalement, 'suivi de partenaire 13-01');
        $this->assertCount(0, $result);
    }

    public function testFindAllSuiviBy(): void
    {
        $signalement = $this->getSignalementByReference('2022-8');

        $result = $this->suiviRepository->findAllSuiviBy($signalement, Suivi::TYPE_TECHNICAL);
        $this->assertCount(3, $result);
        foreach ($result as $suivi) {
            $this->assertEquals(SuiviCategory::ASK_FEEDBACK_SENT, $suivi->getCategory());
        }
    }

    public function testFindAllSuiviByOrdersByCreatedAtAsc(): void
    {
        $signalement = $this->getSignalementByReference('2023-15');

        $result = $this->suiviRepository->findAllSuiviBy($signalement, Suivi::TYPE_PARTNER);
        $this->assertCount(2, $result);
        $this->assertLessThan($result[1]->getCreatedAt(), $result[0]->getCreatedAt());
        $this->assertStringContainsString('premier suivi', $result[0]->getDescription());
        $this->assertStringContainsString('dernier suivi', $result[1]->getDescription());
    }

    public function testFindAllSuiviByReturnsEmptyWhenNoMatchingType(): void
    {
        $signalement = $this->getSignalementByReference('2022-4');

        $result = $this->suiviRepository->findAllSuiviBy($signalement, Suivi::TYPE_TECHNICAL);
        $this->assertCount(0, $result);
    }

    public function testFindExistingEventsForSCHS(): void
    {
        $signalement = $this->getSignalementByReference('2022-8');
        $suivi = $this->createSuivi($signalement, SuiviCategory::MESSAGE_ESABORA_SCHS);
        $suivi->setOriginalData(['keyDataList' => ['ignored', 'schs-event-key-1']]);
        $this->entityManager->flush();

        $result = $this->suiviRepository->findExistingEventsForSCHS();

        $this->assertArrayHasKey('schs-event-key-1', $result);
        $this->assertSame($suivi->getId(), $result['schs-event-key-1']->getId());
    }

    public function testFindExistingEventsForSCHSIgnoresSuiviWithoutOriginalData(): void
    {
        $signalement = $this->getSignalementByReference('2022-8');
        $this->createSuivi($signalement, SuiviCategory::MESSAGE_ESABORA_SCHS);

        $result = $this->suiviRepository->findExistingEventsForSCHS();

        foreach ($result as $suivi) {
            $this->assertNotNull($suivi->getOriginalData());
        }
    }

    public function testFindLastPublicSuivi(): void
    {
        $signalement = $this->getSignalementByReference('2023-15');

        $result = $this->suiviRepository->findLastPublicSuivi($signalement);

        $this->assertNotNull($result);
        $this->assertStringContainsString('dernier suivi de partenaire 13-01', $result->getDescription());
    }

    public function testFindLastPublicSuiviExcludesMessageUsager(): void
    {
        // "2022-4" a un suivi public MESSAGE_USAGER plus récent que le suivi
        // automatique SIGNALEMENT_IS_ACTIVE créé à l'ouverture du dossier : ce dernier
        // doit rester le suivi public "pertinent" puisque MESSAGE_USAGER est exclu.
        $signalement = $this->getSignalementByReference('2022-4');

        $result = $this->suiviRepository->findLastPublicSuivi($signalement);

        $this->assertNotNull($result);
        $this->assertSame(SuiviCategory::SIGNALEMENT_IS_ACTIVE, $result->getCategory());
    }

    public function testFindLastPublicSuiviExcludesDeletedSuivi(): void
    {
        $signalement = $this->getSignalementByReference('2023-15');
        $suivi = $this->createSuivi($signalement, SuiviCategory::MESSAGE_PARTNER, isVisibleForUsager: true, createdAt: new \DateTimeImmutable('+1 day'));
        /** @var \App\Repository\UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(\App\Entity\User::class);
        $admin = $userRepository->findOneBy(['email' => self::USER_ADMIN]);
        $suivi->setDeletedBy($admin);
        $this->entityManager->flush();

        $result = $this->suiviRepository->findLastPublicSuivi($signalement);

        $this->assertNotNull($result);
        $this->assertNotSame($suivi->getId(), $result->getId());
    }

    public function testFindWithWaitingNotificationAndExpiredDelay(): void
    {
        $signalement = $this->getSignalementByReference('2022-8');
        $expired = $this->createSuivi($signalement, SuiviCategory::MESSAGE_PARTNER, createdAt: new \DateTimeImmutable('-1 year'));
        $expired->setWaitingNotification(true);
        $notExpired = $this->createSuivi($signalement, SuiviCategory::MESSAGE_PARTNER, createdAt: new \DateTimeImmutable());
        $notExpired->setWaitingNotification(true);
        $notWaiting = $this->createSuivi($signalement, SuiviCategory::MESSAGE_PARTNER, createdAt: new \DateTimeImmutable('-1 year'));
        $this->entityManager->flush();

        $result = $this->suiviRepository->findWithWaitingNotificationAndExpiredDelay();
        $resultIds = array_map(static fn (Suivi $suivi) => $suivi->getId(), $result);

        $this->assertContains($expired->getId(), $resultIds);
        $this->assertNotContains($notExpired->getId(), $resultIds);
        $this->assertNotContains($notWaiting->getId(), $resultIds);
    }

    private function getSignalementByReference(string $reference): Signalement
    {
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['reference' => $reference]);
        $this->assertNotNull($signalement);

        return $signalement;
    }

    private function createSuivi(
        Signalement $signalement,
        SuiviCategory $category,
        bool $isVisibleForUsager = false,
        ?\DateTimeImmutable $createdAt = null,
    ): Suivi {
        $suivi = (new Suivi())
            ->setSignalement($signalement)
            ->setDescription('')
            ->setCategory($category)
            ->setType(SuiviCategory::getSuiviTypeForSuiviCategory($category))
            ->setIsVisibleForUsager($isVisibleForUsager)
            ->setCreatedAt($createdAt ?? new \DateTimeImmutable());
        $this->entityManager->persist($suivi);
        $this->entityManager->flush();

        return $suivi;
    }
}
