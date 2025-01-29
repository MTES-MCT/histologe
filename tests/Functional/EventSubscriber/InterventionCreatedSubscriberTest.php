<?php

namespace App\Tests\Functional\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Intervention;
use App\Entity\User;
use App\Event\InterventionCreatedEvent;
use App\EventSubscriber\InterventionCreatedSubscriber;
use App\Manager\SuiviManager;
use App\Repository\InterventionRepository;
use App\Repository\UserRepository;
use App\Service\Signalement\VisiteNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class InterventionCreatedSubscriberTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testEventSubscription(): void
    {
        $this->assertArrayHasKey(
            InterventionCreatedEvent::NAME,
            InterventionCreatedSubscriber::getSubscribedEvents()
        );
    }

    public function testBuildVisiteDescription(): void
    {
        $eventDispatcher = new EventDispatcher();
        $visiteNotifier = static::getContainer()->get(VisiteNotifier::class);
        $suiviManager = static::getContainer()->get(SuiviManager::class);
        $htmlSanitizer = self::getContainer()->get('html_sanitizer.sanitizer.app.message_sanitizer');

        /** @var InterventionRepository $interventionRepository */
        $interventionRepository = $this->entityManager->getRepository(Intervention::class);
        $interventions = $interventionRepository->findBy([
            'status' => Intervention::STATUS_PLANNED,
            'type' => InterventionType::VISITE,
        ]);

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@histologe.fr']);
        $interventionCreatedSubscriber = new InterventionCreatedSubscriber($visiteNotifier, $suiviManager, $htmlSanitizer);
        $eventDispatcher->addSubscriber($interventionCreatedSubscriber);

        $intervention = $interventions[0];

        $eventDispatcher->dispatch(
            new InterventionCreatedEvent($intervention, $user),
            InterventionCreatedEvent::NAME
        );

        $this->assertEmailCount(2);
        $this->assertEquals(2, $intervention->getSignalement()->getSuivis()->count());
    }

    public function testInterventionVisitInPast(): void
    {
        $date = (new \DateTimeImmutable())->modify('-1 day');
        $type = InterventionType::VISITE;
        $this->testNbMailSent($date, $type);
        $this->assertEmailCount(0);
    }

    public function testInterventionVisitInFuture(): void
    {
        $date = (new \DateTimeImmutable())->modify('+1 day');
        $type = InterventionType::VISITE;
        $this->testNbMailSent($date, $type);
        $this->assertEmailCount(2);
    }

    public function testInterventionNoVisitInFuture(): void
    {
        $date = (new \DateTimeImmutable())->modify('+1 day');
        $type = InterventionType::ARRETE_PREFECTORAL;
        $this->testNbMailSent($date, $type);
        $this->assertEmailCount(0);
    }

    public function testInterventionNoVisitInPast(): void
    {
        $date = (new \DateTimeImmutable())->modify('-1 day');
        $type = InterventionType::ARRETE_PREFECTORAL;
        $this->testNbMailSent($date, $type);
        $this->assertEmailCount(0);
    }

    private function testNbMailSent(\DateTimeImmutable $date, $type): void
    {
        $eventDispatcher = new EventDispatcher();
        $visiteNotifier = static::getContainer()->get(VisiteNotifier::class);
        $suiviManager = static::getContainer()->get(SuiviManager::class);
        $htmlSanitizer = self::getContainer()->get('html_sanitizer.sanitizer.app.message_sanitizer');

        /** @var InterventionRepository $interventionRepository */
        $interventionRepository = $this->entityManager->getRepository(Intervention::class);
        $intervention = $interventionRepository->findOneBy([
            'status' => Intervention::STATUS_PLANNED,
            'type' => InterventionType::VISITE,
        ]);

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@histologe.fr']);
        $interventionCreatedSubscriber = new InterventionCreatedSubscriber($visiteNotifier, $suiviManager, $htmlSanitizer);
        $eventDispatcher->addSubscriber($interventionCreatedSubscriber);

        $intervention->setScheduledAt($date);
        $intervention->setType($type);

        $eventDispatcher->dispatch(
            new InterventionCreatedEvent($intervention, $user),
            InterventionCreatedEvent::NAME
        );
    }
}
