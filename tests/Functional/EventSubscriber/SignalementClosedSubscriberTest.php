<?php

namespace App\Tests\Functional\EventSubscriber;

use App\Entity\Enum\MotifCloture;
use App\Entity\Signalement;
use App\Entity\User;
use App\Event\SignalementClosedEvent;
use App\EventSubscriber\SignalementClosedSubscriber;
use App\Manager\SignalementManager;
use App\Manager\SuiviManager;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use App\Service\Token\TokenGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SignalementClosedSubscriberTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testEventSubscription(): void
    {
        $this->assertArrayHasKey(SignalementClosedEvent::NAME, SignalementClosedSubscriber::getSubscribedEvents());
    }

    public function testOnSignalementClosedForAllPartnerCallNotificationMethods()
    {
        /** @var Signalement $signalementClosed */
        $signalementClosed = $this->entityManager->getRepository(Signalement::class)->findOneBy(['reference' => '2022-2']);
        $mailsPartner = $this
            ->entityManager
            ->getRepository(Signalement::class)
            ->findUsersPartnerEmailAffectedToSignalement($signalementClosed->getId());

        $genericMailsPartner = $this
            ->entityManager
            ->getRepository(Signalement::class)
            ->findPartnersEmailAffectedToSignalement($signalementClosed->getId());

        $sendToPartners = array_merge($mailsPartner, $genericMailsPartner);

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['statut' => User::STATUS_ACTIVE]);

        $notitificationServiceMock = $this->createMock(NotificationService::class);
        $notitificationServiceMock
            ->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    NotificationService::TYPE_SIGNALEMENT_CLOSED_TO_USAGER,
                    $signalementClosed->getMailUsagers(),
                    [
                        'motif_cloture' => $signalementClosed->getMotifCloture()->label(),
                        'link' => '',
                    ],
                    $signalementClosed->getTerritory(),
                ],
                [
                    NotificationService::TYPE_SIGNALEMENT_CLOSED_TO_PARTNERS,
                    $sendToPartners,
                    [
                        'ref_signalement' => $signalementClosed->getReference(),
                        'motif_cloture' => $signalementClosed->getMotifCloture()->label(),
                        'closed_by' => $signalementClosed->getClosedBy()->getNomComplet(),
                        'partner_name' => $signalementClosed->getClosedBy()->getPartner()->getNom(),
                        'link' => '',
                    ],
                    $signalementClosed->getTerritory(),
                ]
            )->willReturn(true);
        $userRepositoryMock = $this->createMock(UserRepository::class);
        $urlGeneratorMock = $this->createMock(UrlGeneratorInterface::class);
        $parameterBagMock = $this->createMock(ParameterBagInterface::class);
        $tokenGeneratorMock = $this->createMock(TokenGeneratorInterface::class);
        $securityMock = $this->createMock(Security::class);
        $securityMock->expects($this->once())->method('getUser')->willReturn($user);

        $signalementManager = static::getContainer()->get(SignalementManager::class);
        $suiviManager = static::getContainer()->get(SuiviManager::class);

        $signalementClosedSubscriber = new SignalementClosedSubscriber(
            $notitificationServiceMock,
            $signalementManager,
            $userRepositoryMock,
            $urlGeneratorMock,
            $parameterBagMock,
            $tokenGeneratorMock,
            $suiviManager,
            $securityMock
        );

        $signalementClosedEvent = new SignalementClosedEvent(
            $signalementClosed,
            [
                'motif_suivi' => 'Lorem ipsum suivi sit amet, consectetur adipiscing elit.',
                'motif_cloture' => MotifCloture::tryFrom('NON_DECENCE'),
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
