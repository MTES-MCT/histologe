<?php

namespace App\Tests\Functional\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Intervention;
use App\Entity\User;
use App\Event\InterventionUpdatedByEsaboraEvent;
use App\EventSubscriber\InterventionUpdatedByEsaboraSubscriber;
use App\Manager\SuiviManager;
use App\Repository\InterventionRepository;
use App\Repository\UserRepository;
use App\Service\Signalement\VisiteNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class InterventionUpdatedByEsaboraSubscriberTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testBuildVisiteUpdated(): void
    {
        $eventDispatcher = new EventDispatcher();
        $visiteNotifier = static::getContainer()->get(VisiteNotifier::class);
        $suiviManager = static::getContainer()->get(SuiviManager::class);

        /** @var InterventionRepository $interventionRepository */
        $interventionRepository = $this->entityManager->getRepository(Intervention::class);
        $interventions = $interventionRepository->findBy([
            'status' => Intervention::STATUS_PLANNED,
            'type' => InterventionType::VISITE,
        ]);

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@signal-logement.fr']);
        $interventionUpdatedByEsaboraSubscriber = new InterventionUpdatedByEsaboraSubscriber($visiteNotifier, $suiviManager, true);
        $eventDispatcher->addSubscriber($interventionUpdatedByEsaboraSubscriber);

        $intervention = $interventions[0];
        $intervention->setPreviousScheduledAt($intervention->getScheduledAt());
        $eventDispatcher->dispatch(
            new InterventionUpdatedByEsaboraEvent(
                $intervention,
                $user,
                $user->getPartnerInTerritoryOrFirstOne($intervention->getSignalement()->getTerritory())
            ),
            InterventionUpdatedByEsaboraEvent::NAME
        );

        $this->assertEmailCount(1);
        $this->assertEquals(2, $intervention->getSignalement()->getSuivis()->count());
        $this->assertStringContainsString('a été modifiée', $intervention->getSignalement()->getSuivis()->last()->getDescription());
    }
}
