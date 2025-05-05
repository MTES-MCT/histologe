<?php

namespace App\Tests\Functional\EventSubscriber;

use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\NotificationType;
use App\Entity\Enum\UserStatus;
use App\Entity\Notification;
use App\Entity\Signalement;
use App\Entity\User;
use App\Event\SignalementClosedEvent;
use App\EventSubscriber\SignalementClosedSubscriber;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Repository\NotificationRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SignalementClosedSubscriberTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private SignalementRepository $signalementRepository;
    private UserRepository $userRepository;
    private NotificationRepository $notificationRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $this->userRepository = $this->entityManager->getRepository(User::class);
        $this->notificationRepository = $this->entityManager->getRepository(Notification::class);
    }

    public function testEventSubscription(): void
    {
        $this->assertArrayHasKey(SignalementClosedEvent::NAME, SignalementClosedSubscriber::getSubscribedEvents());
    }

    public function testOnSignalementClosedForAllPartnerCallNotificationMethods()
    {
        /** @var Signalement $signalementClosed */
        $signalementClosed = $this->signalementRepository->findOneBy(['reference' => '2024-08']);

        $user = $this->userRepository->findOneBy(['statut' => UserStatus::ACTIVE]);

        $securityMock = $this->createMock(Security::class);
        $securityMock->expects($this->once())->method('getUser')->willReturn($user);

        $signalementManager = static::getContainer()->get(SignalementManager::class);
        $suiviManager = static::getContainer()->get(SuiviManager::class);

        $signalementClosedSubscriber = new SignalementClosedSubscriber(
            $signalementManager,
            $suiviManager,
            $securityMock
        );

        $signalementClosedEvent = new SignalementClosedEvent(
            $signalementClosed,
            [
                'motif_suivi' => 'Lorem ipsum suivi sit amet, consectetur adipiscing elit.',
                'motif_cloture' => MotifCloture::tryFrom('NON_DECENCE'),
                'suivi_public' => '1',
                'subject' => 'tous les partenaires',
                'closed_for' => 'all',
            ]
        );
        $signalementClosed->setMotifCloture(MotifCloture::tryFrom('NON_DECENCE'));

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($signalementClosedSubscriber);
        $event = $dispatcher->dispatch($signalementClosedEvent, SignalementClosedEvent::NAME);

        $this->assertInstanceOf(Signalement::class, $event->getSignalement());
        $this->assertIsArray($event->getParams());
        $this->assertEmailCount(2);
        /** @var NotificationEmail $clotureMail */
        $clotureMail = $this->getMailerMessages()[0];
        $this->assertEmailSubjectContains($clotureMail, 'Clôture du signalement');
        $this->assertEmailAddressContains($clotureMail, 'to', 'ne-pas-repondre@signal-logement.beta.gouv.fr');
        $this->assertCount(2, $clotureMail->getBcc());
        $this->assertEmailAddressContains($clotureMail, 'bcc', 'partenaire-34-04@signal-logement.fr');
        $this->assertEmailAddressContains($clotureMail, 'bcc', 'user-partenaire-34-02@signal-logement.fr');

        $notifications = $this->notificationRepository->findBy(['signalement' => $signalementClosed, 'type' => NotificationType::CLOTURE_SIGNALEMENT]);
        $this->assertCount(5, $notifications);
    }
}
