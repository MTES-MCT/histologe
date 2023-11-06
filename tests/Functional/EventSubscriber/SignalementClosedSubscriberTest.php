<?php

namespace App\Tests\Functional\EventSubscriber;

use App\Entity\Enum\MotifCloture;
use App\Entity\Signalement;
use App\Entity\User;
use App\Event\SignalementClosedEvent;
use App\EventSubscriber\SignalementClosedSubscriber;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Token\TokenGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SignalementClosedSubscriberTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private SignalementRepository $signalementRepository;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->signalementRepository = $this->entityManager->getRepository(Signalement::class);
        $this->userRepository = $this->entityManager->getRepository(User::class);
    }

    public function testEventSubscription(): void
    {
        $this->assertArrayHasKey(SignalementClosedEvent::NAME, SignalementClosedSubscriber::getSubscribedEvents());
    }

    public function testOnSignalementClosedForAllPartnerCallNotificationMethods()
    {
        /** @var Signalement $signalementClosed */
        $signalementClosed = $this->signalementRepository->findOneBy(['reference' => '2022-2']);
        $mailsPartner = $this->signalementRepository
            ->findUsersPartnerEmailAffectedToSignalement($signalementClosed->getId());

        $genericMailsPartner = $this->signalementRepository
            ->findPartnersEmailAffectedToSignalement($signalementClosed->getId());

        $sendToPartners = array_merge($mailsPartner, $genericMailsPartner);

        $user = $this->userRepository->findOneBy(['statut' => User::STATUS_ACTIVE]);

        $notificationMailerRegistryMock = $this->createMock(NotificationMailerRegistry::class);
        $notificationMailerRegistryMock
            ->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_SIGNALEMENT_CLOSED_TO_USAGER,
                        to: $signalementClosed->getMailUsagers(),
                        territory: $signalementClosed->getTerritory(),
                        signalement: $signalementClosed
                    ),
                ],
                [
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_SIGNALEMENT_CLOSED_TO_PARTNERS,
                        to: $sendToPartners,
                        territory: $signalementClosed->getTerritory(),
                        signalement: $signalementClosed
                    ),
                ]
            )->willReturn(true);
        $tokenGeneratorMock = $this->createMock(TokenGeneratorInterface::class);
        $securityMock = $this->createMock(Security::class);
        $securityMock->expects($this->once())->method('getUser')->willReturn($user);

        $signalementManager = static::getContainer()->get(SignalementManager::class);
        $suiviManager = static::getContainer()->get(SuiviManager::class);

        $signalementClosedSubscriber = new SignalementClosedSubscriber(
            $notificationMailerRegistryMock,
            $signalementManager,
            $tokenGeneratorMock,
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

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($signalementClosedSubscriber);
        $event = $dispatcher->dispatch($signalementClosedEvent, SignalementClosedEvent::NAME);

        $this->assertInstanceOf(Signalement::class, $event->getSignalement());
        $this->assertNull($event->getAffectation());
        $this->assertIsArray($event->getParams());
    }
}
