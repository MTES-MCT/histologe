<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\NotificationType;
use App\Factory\NotificationFactory;
use App\Repository\AffectationRepository;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\SuiviRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadNotification extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly SignalementRepository $signalementRepository,
        private readonly NotificationFactory $notificationFactory,
        private readonly PartnerRepository $partnerRepository,
        private readonly AffectationRepository $affectationRepository,
        private readonly SuiviRepository $suiviRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $zoneRows = Yaml::parseFile(__DIR__.'/../Files/Notification.yml');
        foreach ($zoneRows['notifications'] as $row) {
            $this->loadNotification($manager, $row);
        }
        $manager->flush();
    }

    /**
     * @param array<string, mixed> $row
     */
    private function loadNotification(ObjectManager $manager, array $row): void
    {
        $user = $this->userRepository->findOneBy(['email' => $row['user']]);
        $type = NotificationType::from($row['type']);
        $signalement = $this->signalementRepository->findOneBy(['reference' => $row['signalement']]);
        $suivi = null;
        $affectation = null;
        if (isset($row['affectation'])) {
            $partner = $this->partnerRepository->findOneBy(['nom' => $row['affectation']]);
            $affectation = $this->affectationRepository->findOneBy(['signalement' => $signalement, 'partner' => $partner]);
        }
        if ('CLOTURE_SIGNALEMENT' === $row['type']) {
            $suivi = $this->suiviRepository->findOneBy(['signalement' => $signalement, 'context' => 'signalementClosed']);
        }
        if (!empty($row['suivi'])) {
            $suivi = $this->suiviRepository->findOneBy(['signalement' => $signalement, 'description' => $row['suivi']]);
        }

        $notification = $this->notificationFactory->createInstanceFrom(
            user: $user,
            type: $type,
            suivi: $suivi,
            affectation: $affectation,
            signalement: $signalement
        );
        $manager->persist($notification);
    }

    public function getOrder(): int
    {
        return 23;
    }
}
