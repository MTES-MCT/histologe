<?php

namespace App\Tests\Functional\EventSubscriber;

use App\Entity\Enum\InterventionType;
use App\Entity\Intervention;
use App\Entity\User;
use App\Event\InterventionUpdatedByEsaboraEvent;
use App\EventSubscriber\InterventionUpdatedByEsaboraSubscriber;
use App\Manager\SuiviManager;
use App\Repository\SignalementRepository;
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

    /**
     * @dataProvider provideSignalement
     */
    public function testBuildVisiteUpdated(string $reference, int $countMail): void
    {
        $eventDispatcher = new EventDispatcher();
        $visiteNotifier = static::getContainer()->get(VisiteNotifier::class);
        $suiviManager = static::getContainer()->get(SuiviManager::class);

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $signalement = $signalementRepository->findOneBy(['reference' => $reference]);
        $intervention = $signalement->getInterventions()->filter(
            fn (Intervention $intervention) => Intervention::STATUS_PLANNED === $intervention->getStatus()
                && InterventionType::VISITE === $intervention->getType()
        )->first();

        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@signal-logement.fr']);
        $interventionUpdatedByEsaboraSubscriber = new InterventionUpdatedByEsaboraSubscriber($visiteNotifier, $suiviManager);
        $eventDispatcher->addSubscriber($interventionUpdatedByEsaboraSubscriber);

        $intervention->setPreviousScheduledAt($intervention->getScheduledAt());
        $eventDispatcher->dispatch(
            new InterventionUpdatedByEsaboraEvent(
                $intervention,
                $user,
                $user->getPartnerInTerritoryOrFirstOne($intervention->getSignalement()->getTerritory())
            ),
            InterventionUpdatedByEsaboraEvent::NAME
        );

        $this->assertEmailCount($countMail);
        $this->assertEquals(2, $intervention->getSignalement()->getSuivis()->count());
        $this->assertStringContainsString('a été modifiée', $intervention->getSignalement()->getSuivis()->last()->getDescription());
    }

    public function provideSignalement(): \Generator
    {
        yield 'Do not notify on signalement tiers' => ['2022-1', 0];
        yield 'Notify on signalement occupant' => ['2023-9', 1];
    }
}
