<?php

namespace App\Tests\Functional\Repository;

use App\Entity\Enum\NotificationType;
use App\Entity\Notification;
use App\Entity\Signalement;
use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Service\ListFilters\SearchNotification;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class NotificationRepositoryTest extends KernelTestCase
{
    private const string USER_EMAIL = 'admin-01@signal-logement.fr';
    private const string SIGNALEMENT_REFERENCE = '2022-10';

    private EntityManagerInterface $entityManager;
    private NotificationRepository $notificationRepository;
    private User $user;
    private Signalement $signalement;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
        $this->notificationRepository = $this->entityManager->getRepository(Notification::class);

        /** @var User $user */
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::USER_EMAIL]);
        $this->user = $user;

        /** @var Signalement $signalement */
        $signalement = $this->entityManager->getRepository(Signalement::class)->findOneBy(['reference' => self::SIGNALEMENT_REFERENCE]);
        $this->signalement = $signalement;
    }

    private function createNotification(
        NotificationType $type,
        ?Signalement $signalement = null,
        bool $isSeen = false,
        bool $deleted = false,
        bool $waitMailingSummary = false,
    ): Notification {
        $notification = (new Notification())
            ->setUser($this->user)
            ->setType($type)
            ->setSignalement($signalement)
            ->setIsSeen($isSeen)
            ->setDeleted($deleted)
            ->setWaitMailingSummary($waitMailingSummary);

        $this->entityManager->persist($notification);

        return $notification;
    }

    public function testFindFilteredPaginatedIncludesMentionAndExcludesUsagerAndDeleted(): void
    {
        $mention = $this->createNotification(NotificationType::NOUVELLE_MENTION);
        $suivi = $this->createNotification(NotificationType::NOUVEAU_SUIVI);
        $excludedUsager = $this->createNotification(NotificationType::SUIVI_USAGER);
        $excludedDeleted = $this->createNotification(NotificationType::NOUVEAU_SUIVI, deleted: true);
        $this->entityManager->flush();

        $searchNotification = new SearchNotification($this->user);
        $result = $this->notificationRepository->findFilteredPaginated($searchNotification, 100);
        $ids = array_map(static fn (Notification $n) => $n->getId(), iterator_to_array($result));

        $this->assertContains($mention->getId(), $ids);
        $this->assertContains($suivi->getId(), $ids);
        $this->assertNotContains($excludedUsager->getId(), $ids);
        $this->assertNotContains($excludedDeleted->getId(), $ids);
    }

    public function testMarkUserNotificationsAsSeenIncludesMentionButNotUsager(): void
    {
        $mention = $this->createNotification(NotificationType::NOUVELLE_MENTION);
        $usager = $this->createNotification(NotificationType::SUIVI_USAGER);
        $this->entityManager->flush();

        $this->notificationRepository->markUserNotificationsAsSeen($this->user);
        $this->entityManager->refresh($mention);
        $this->entityManager->refresh($usager);

        $this->assertTrue($mention->getIsSeen());
        $this->assertFalse($usager->getIsSeen());
    }

    public function testMarkUserNotificationsAsSeenWithSpecificIds(): void
    {
        $mention = $this->createNotification(NotificationType::NOUVELLE_MENTION);
        $suivi = $this->createNotification(NotificationType::NOUVEAU_SUIVI);
        $this->entityManager->flush();

        $this->notificationRepository->markUserNotificationsAsSeen($this->user, [$mention->getId()]);
        $this->entityManager->refresh($mention);
        $this->entityManager->refresh($suivi);

        $this->assertTrue($mention->getIsSeen());
        $this->assertFalse($suivi->getIsSeen());
    }

    public function testDeleteUserNotificationsIncludesMentionButNotUsager(): void
    {
        $mention = $this->createNotification(NotificationType::NOUVELLE_MENTION);
        $usager = $this->createNotification(NotificationType::SUIVI_USAGER);
        $this->entityManager->flush();

        $this->notificationRepository->deleteUserNotifications($this->user);
        $this->entityManager->refresh($mention);
        $this->entityManager->refresh($usager);

        $this->assertTrue($mention->isDeleted());
        $this->assertFalse($usager->isDeleted());
    }

    public function testFindWaitingSummaryForUserAndMassUpdate(): void
    {
        $waiting = $this->createNotification(NotificationType::NOUVELLE_MENTION, waitMailingSummary: true);
        $notWaiting = $this->createNotification(NotificationType::NOUVEAU_SUIVI, waitMailingSummary: false);
        $this->entityManager->flush();

        $result = $this->notificationRepository->findWaitingSummaryForUser($this->user);
        $ids = array_map(static fn (Notification $n) => $n->getId(), $result);
        $this->assertContains($waiting->getId(), $ids);
        $this->assertNotContains($notWaiting->getId(), $ids);

        $now = new \DateTimeImmutable();
        $this->notificationRepository->massUpdate([$waiting], ['waitMailingSummary' => false, 'mailingSummarySentAt' => $now]);
        $this->entityManager->refresh($waiting);

        $this->assertFalse($waiting->isWaitMailingSummary());
        $this->assertEquals($now->format('Y-m-d H:i:s'), $waiting->getMailingSummarySentAt()->format('Y-m-d H:i:s'));
    }

    public function testCleanOlderThanKeepsSuiviUsager(): void
    {
        $old = $this->createNotification(NotificationType::NOUVEAU_SUIVI);
        $oldUsager = $this->createNotification(NotificationType::SUIVI_USAGER);
        $this->entityManager->flush();

        $this->entityManager->createQuery('UPDATE App\Entity\Notification n SET n.createdAt = :date WHERE n.id IN (:ids)')
            ->setParameter('date', new \DateTimeImmutable('-40 days'))
            ->setParameter('ids', [$old->getId(), $oldUsager->getId()])
            ->execute();
        $this->entityManager->clear();

        $this->notificationRepository->cleanOlderThan();
        $this->entityManager->clear();

        $this->assertNull($this->notificationRepository->find($old->getId()));
        $this->assertNotNull($this->notificationRepository->find($oldUsager->getId()));
    }

    public function testDeleteBySignalement(): void
    {
        $notification = $this->createNotification(NotificationType::NOUVEAU_SUIVI, $this->signalement);
        $this->entityManager->flush();

        $this->notificationRepository->deleteBySignalement($this->signalement);
        $this->entityManager->clear();

        $this->assertNull($this->notificationRepository->find($notification->getId()));
    }

    public function testFindUnseenNotificationsBy(): void
    {
        $unseenMention = $this->createNotification(NotificationType::NOUVELLE_MENTION, $this->signalement, isSeen: false);
        $seen = $this->createNotification(NotificationType::NOUVEAU_SUIVI, $this->signalement, isSeen: true);
        $unseenOther = $this->createNotification(NotificationType::CLOTURE_SIGNALEMENT, $this->signalement, isSeen: false);
        $this->entityManager->flush();

        $allUnseen = $this->notificationRepository->findUnseenNotificationsBy($this->signalement, $this->user);
        $ids = array_map(static fn (Notification $n) => $n->getId(), $allUnseen);
        $this->assertContains($unseenMention->getId(), $ids);
        $this->assertContains($unseenOther->getId(), $ids);
        $this->assertNotContains($seen->getId(), $ids);

        $filtered = $this->notificationRepository->findUnseenNotificationsBy(
            $this->signalement,
            $this->user,
            [NotificationType::NOUVELLE_MENTION]
        );
        $filteredIds = array_map(static fn (Notification $n) => $n->getId(), $filtered);
        $this->assertContains($unseenMention->getId(), $filteredIds);
        $this->assertNotContains($unseenOther->getId(), $filteredIds);
    }
}
