<?php

namespace App\Tests\Functional\Repository\Query\Dashboard;

use App\Entity\Enum\NotificationType;
use App\Entity\Notification;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Repository\Query\Dashboard\DossiersSuivisUsagerQuery;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DossiersSuivisUsagerQueryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private DossiersSuivisUsagerQuery $dossiersSuivisUsagerQuery;
    public const USER_ADMIN = 'admin-01@signal-logement.fr';

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
        $this->dossiersSuivisUsagerQuery = static::getContainer()->get(DossiersSuivisUsagerQuery::class);
    }

    public function testFindSuivisUsagersWithoutAskFeedbackBefore(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        $tabQueryParameter = new TabQueryParameters(
            mesDossiersMessagesUsagers: '0',
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );
        $result = $this->dossiersSuivisUsagerQuery->findSuivisUsagersWithoutAskFeedbackBefore($user, $tabQueryParameter);
        $this->assertIsArray($result);

        if (count($result) > 0) {
            $this->assertIsArray($result[0]);
            $this->assertArrayHasKey('reference', $result[0]);
            $this->assertEquals('2022-4', $result[0]['reference']);
        }
    }

    public function testCountSuivisUsagersWithoutAskFeedbackBefore(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        $tabQueryParameter = new TabQueryParameters(
            mesDossiersMessagesUsagers: '0',
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );

        $result = $this->dossiersSuivisUsagerQuery->countSuivisUsagersWithoutAskFeedbackBefore($user, $tabQueryParameter);
        $this->assertIsInt($result);
        $this->assertEquals(1, $result);
    }

    public function testFindSuivisPostCloture(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        $tabQueryParameter = new TabQueryParameters(
            mesDossiersMessagesUsagers: '0',
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );
        $result = $this->dossiersSuivisUsagerQuery->findSuivisPostCloture($user, $tabQueryParameter);
        $this->assertIsArray($result);

        if (count($result) > 0) {
            $this->assertIsArray($result[0]);
            $this->assertArrayHasKey('reference', $result[0]);
            $this->assertEquals('2022-2', $result[0]['reference']);
        }
    }

    public function testCountSuivisPostCloture(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        $tabQueryParameter = new TabQueryParameters(
            mesDossiersMessagesUsagers: '0',
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );
        $result = $this->dossiersSuivisUsagerQuery->countSuivisPostCloture($user, $tabQueryParameter);
        $this->assertIsInt($result);
        $this->assertEquals(1, $result);
    }

    public function testCountSuivisPostClotureSeen(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        /** @var Signalement $signalementWithSuiviPostCloture */
        $signalementWithSuiviPostCloture = $this->entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-2']);
        /** @var Notification $notification */
        $notification = $this->entityManager->getRepository(Notification::class)->findOneBy([
            'signalement' => $signalementWithSuiviPostCloture,
            'user' => $user,
            'type' => NotificationType::NOUVEAU_SUIVI,
        ]);
        $notification->setSeenAt(new \DateTimeImmutable());
        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        $tabQueryParameter = new TabQueryParameters(
            mesDossiersMessagesUsagers: '0',
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );
        $result = $this->dossiersSuivisUsagerQuery->countSuivisPostCloture($user, $tabQueryParameter);
        $this->assertIsInt($result);
        $this->assertEquals(0, $result);
    }

    public function testCountSuivisPostClotureDeleted(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        /** @var Signalement $signalementWithSuiviPostCloture */
        $signalementWithSuiviPostCloture = $this->entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-2']);
        /** @var Notification $notification */
        $notification = $this->entityManager->getRepository(Notification::class)->findOneBy([
            'signalement' => $signalementWithSuiviPostCloture,
            'user' => $user,
            'type' => NotificationType::NOUVEAU_SUIVI,
        ]);
        $notification->setDeleted(true);
        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        $tabQueryParameter = new TabQueryParameters(
            mesDossiersMessagesUsagers: '0',
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );
        $result = $this->dossiersSuivisUsagerQuery->countSuivisPostCloture($user, $tabQueryParameter);
        $this->assertIsInt($result);
        $this->assertEquals(0, $result);
    }

    public function testCountSuivisPostClotureNoNotification(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        /** @var Signalement $signalementWithSuiviPostCloture */
        $signalementWithSuiviPostCloture = $this->entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-2']);
        /** @var Notification $notification */
        $notification = $this->entityManager->getRepository(Notification::class)->findOneBy([
            'signalement' => $signalementWithSuiviPostCloture,
            'user' => $user,
            'type' => NotificationType::NOUVEAU_SUIVI,
        ]);
        $this->entityManager->remove($notification);
        $this->entityManager->flush();

        $tabQueryParameter = new TabQueryParameters(
            mesDossiersMessagesUsagers: '0',
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );
        $result = $this->dossiersSuivisUsagerQuery->countSuivisPostCloture($user, $tabQueryParameter);
        $this->assertIsInt($result);
        $this->assertEquals(0, $result);
    }

    public function testFindSuivisUsagerOrPoursuiteWithAskFeedbackBefore(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        $tabQueryParameter = new TabQueryParameters(
            mesDossiersMessagesUsagers: '1',
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );
        $result = $this->dossiersSuivisUsagerQuery->findSuivisUsagerOrPoursuiteWithAskFeedbackBefore($user, $tabQueryParameter);
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
        $result = $this->dossiersSuivisUsagerQuery->countSuivisUsagerOrPoursuiteWithAskFeedbackBefore($user, $tabQueryParameter);
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function testCountAllMessagesUsagers(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_ADMIN]);
        $params = new TabQueryParameters();
        $result = $this->dossiersSuivisUsagerQuery->countAllMessagesUsagers($user, $params);
        $this->assertIsObject($result);
        $this->assertTrue(method_exists($result, 'total'));
    }
}
