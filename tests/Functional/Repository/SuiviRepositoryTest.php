<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Service\DashboardTabPanel\TabQueryParameters;
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

    public function testFindSignalementsForFirstAskFeedbackRelance(): void
    {
        $result = $this->suiviRepository->findSignalementsForFirstAskFeedbackRelance();
        $this->assertCount(6, $result);
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        for ($i = 0; $i < count($result); ++$i) {
            $signalement = $signalementRepository->findOneBy(['id' => $result[$i]]);
            $this->assertContains($signalement->getReference(), ['2023-13', '2023-19', '2023-20', '2023-120', '2024-01', '2024-02']);
        }
    }

    public function testFindSignalementsForSecondAskFeedbackRelance(): void
    {
        $result = $this->suiviRepository->findSignalementsForSecondAskFeedbackRelance();
        $this->assertCount(1, $result);
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['id' => $result[0]]);
        $this->assertEquals('2023-14', $signalement->getReference());
    }

    public function testFindSignalementsForThirdAskFeedbackRelance(): void
    {
        $result = $this->suiviRepository->findSignalementsForThirdAskFeedbackRelance();
        $this->assertCount(1, $result);
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['id' => $result[0]]);
        $this->assertEquals('2023-15', $signalement->getReference());
    }

    public function testFindSignalementsForLoopAskFeedbackRelance(): void
    {
        $result = $this->suiviRepository->findSignalementsForLoopAskFeedbackRelance();
        $this->assertCount(1, $result);
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $signalement = $signalementRepository->findOneBy(['id' => $result[0]]);
        $this->assertEquals('2022-8', $signalement->getReference());
    }

    public function testFindLastSignalementsWithUserSuivi(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        $territory = null;
        $limit = 5;
        $result = $this->suiviRepository->findLastSignalementsWithUserSuivi($user, $territory, $limit);
        $this->assertIsArray($result);
        $this->assertLessThanOrEqual($limit, count($result));
        if (count($result) > 0) {
            $row = $result[0];
            $this->assertArrayHasKey('reference', $row);
            $this->assertArrayHasKey('nomOccupant', $row);
            $this->assertArrayHasKey('prenomOccupant', $row);
            $this->assertArrayHasKey('adresseOccupant', $row);
            $this->assertArrayHasKey('uuid', $row);
            $this->assertArrayHasKey('statut', $row);
            $this->assertArrayHasKey('suiviCreatedAt', $row);
            $this->assertArrayHasKey('suiviCategory', $row);
            $this->assertArrayHasKey('suiviIsPublic', $row);
            $this->assertArrayHasKey('hasNewerSuivi', $row);
        }
    }

    public function testCountSuivisUsagersWithoutAskFeedbackBefore(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        $tabQueryParameter = new TabQueryParameters(
            mesDossiersMessagesUsagers: '1',
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );

        $result = $this->suiviRepository->countSuivisUsagersWithoutAskFeedbackBefore($user, $tabQueryParameter);
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testFindSuivisUsagersWithoutAskFeedbackBefore(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        $tabQueryParameter = new TabQueryParameters(
            mesDossiersMessagesUsagers: '1',
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );
        $result = $this->suiviRepository->findSuivisUsagersWithoutAskFeedbackBefore($user, $tabQueryParameter);
        $this->assertIsArray($result);

        if (count($result) > 0) {
            $this->assertInstanceOf(Suivi::class, $result[0]);
        }
    }

    public function testCountSuivisPostCloture(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        $tabQueryParameter = new TabQueryParameters(
            mesDossiersMessagesUsagers: '1',
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );
        $result = $this->suiviRepository->countSuivisPostCloture($user, $tabQueryParameter);
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testFindSuivisPostCloture(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        $tabQueryParameter = new TabQueryParameters(
            mesDossiersMessagesUsagers: '1',
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );
        $result = $this->suiviRepository->findSuivisPostCloture($user, $tabQueryParameter);
        $this->assertIsArray($result);

        if (count($result) > 0) {
            $this->assertInstanceOf(Suivi::class, $result[0]);
        }
    }

    public function testCountSuivisUsagerOrPoursuiteWithAskFeedbackBefore(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        $tabQueryParameter = new TabQueryParameters(
            mesDossiersMessagesUsagers: '1',
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );
        $result = $this->suiviRepository->countSuivisUsagerOrPoursuiteWithAskFeedbackBefore($user, $tabQueryParameter);
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testFindSuivisUsagerOrPoursuiteWithAskFeedbackBefore(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        $tabQueryParameter = new TabQueryParameters(
            mesDossiersMessagesUsagers: '1',
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );
        $result = $this->suiviRepository->findSuivisUsagerOrPoursuiteWithAskFeedbackBefore($user, $tabQueryParameter);
        $this->assertIsArray($result);

        if (count($result) > 0) {
            $this->assertInstanceOf(Suivi::class, $result[0]);
        }
    }

    public function testCountAllMessagesUsagers(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        $params = new TabQueryParameters();
        $result = $this->suiviRepository->countAllMessagesUsagers($user, $params);
        $this->assertIsObject($result);
        $this->assertTrue(method_exists($result, 'total'));
    }

    public function testFindLastSignalementsWithOtherUserSuivi(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        $tabQueryParameter = new TabQueryParameters(
            mesDossiersActiviteRecente: '1',
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );
        $result = $this->suiviRepository->findLastSignalementsWithOtherUserSuivi($user, $tabQueryParameter);
        $this->assertIsArray($result);
    }
}
