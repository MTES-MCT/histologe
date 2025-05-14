<?php

namespace App\Tests\Functional\Service\Signalement;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Event\SuiviViewedEvent;
use App\Repository\NotificationRepository;
use App\Service\Signalement\SuiviSeenMarker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SuiviSeenMarkerTest extends KernelTestCase
{
    public function testMarkSeenByUsagerMarksOnlyPublicSuivis(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $eventDispatcher = $container->get('event_dispatcher');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine')->getManager();
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'joude.bellingham@uk.com']);
        $signalement = $entityManager->getRepository(Signalement::class)->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000015']);
        $eventDispatcher->dispatch(
            new SuiviViewedEvent($signalement, $user),
            SuiviViewedEvent::NAME
        );
        /** @var NotificationRepository $notificationRepository */
        $notificationRepository = $container->get(NotificationRepository::class);
        $marker = new SuiviSeenMarker($notificationRepository);
        $marker->markSeenByUsager($signalement);

        $publicSuivis = $signalement->getSuivis()->filter(fn ($s) => $s->getIsPublic());
        $internalSuivis = $signalement->getSuivis()->filter(fn ($s) => !$s->getIsPublic());

        /** @var Suivi $suivi */
        foreach ($publicSuivis as $suivi) {
            $this->assertTrue($suivi->isSeenByUsager(), sprintf('Suivi #%d public doit être marqué comme vu', $suivi->getId()));
        }

        foreach ($internalSuivis as $suivi) {
            $this->assertNull($suivi->isSeenByUsager(), sprintf('Suivi #%d doit être marqué comme non vu car interne', $suivi->getId()));
        }
    }
}
