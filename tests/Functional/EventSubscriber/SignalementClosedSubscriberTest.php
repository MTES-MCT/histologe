<?php

namespace App\Tests\Functional\EventSubscriber;

use App\Dto\SignalementAffectationClose;
use App\Entity\Enum\MotifCloture;
use App\Entity\Enum\NotificationType;
use App\Entity\Notification;
use App\Entity\Signalement;
use App\Entity\User;
use App\Event\SignalementClosedEvent;
use App\EventSubscriber\SignalementClosedSubscriber;
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

    public function testOnSignalementClosedForAllPartnerCallNotificationMethods(): void
    {
        /** @var Signalement $signalementClosed */
        $signalementClosed = $this->signalementRepository->findOneBy(['reference' => '2024-08']);

        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-34-02@signal-logement.fr']);

        $securityMock = $this->createMock(Security::class);
        $securityMock->expects($this->once())->method('getUser')->willReturn($user);

        $suiviManager = static::getContainer()->get(SuiviManager::class);

        $signalementClosedSubscriber = new SignalementClosedSubscriber(
            $suiviManager,
            $securityMock
        );

        $signalementAffectationClose = (new SignalementAffectationClose())
            ->setSignalement($signalementClosed)
            ->setType('all')
            ->setDescription('Lorem ipsum dolor sit amet, consectetur adipiscing elit.')
            ->setMotifCloture(MotifCloture::tryFrom('NON_DECENCE'))
            ->setSubject('tous les partenaires');
        $signalementClosedEvent = new SignalementClosedEvent(
            $signalementAffectationClose,
            $user->getPartnerInTerritoryOrFirstOne($signalementClosed->getTerritory())
        );
        $signalementClosed->setMotifCloture(MotifCloture::tryFrom('NON_DECENCE'));

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($signalementClosedSubscriber);
        $event = $dispatcher->dispatch($signalementClosedEvent, SignalementClosedEvent::NAME);

        $this->assertInstanceOf(Signalement::class, $event->getSignalementAffectationClose()->getSignalement());
        $this->assertEmailCount(2);
        /** @var NotificationEmail $clotureMail */
        $clotureMail = $this->getMailerMessages()[0];
        $this->assertEmailSubjectContains($clotureMail, 'Clôture du signalement');
        $this->assertEmailAddressContains($clotureMail, 'to', 'ne-pas-repondre@signal-logement.beta.gouv.fr');
        $this->assertCount(2, $clotureMail->getBcc());
        $this->assertEmailAddressContains($clotureMail, 'bcc', 'partenaire-34-04@signal-logement.fr');
        $this->assertEmailAddressContains($clotureMail, 'bcc', 'admin-territoire-34-01@signal-logement.fr');

        $notifications = $this->notificationRepository->findBy(['signalement' => $signalementClosed, 'type' => NotificationType::CLOTURE_SIGNALEMENT]);
        $this->assertCount(4, $notifications);
    }
}
