<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Enum\SuiviDelayedType;
use App\Entity\SuiviDelayed;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadSuiviDelayedData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function load(ObjectManager $manager): void
    {
        $suiviRows = Yaml::parseFile(__DIR__.'/../Files/SuiviDelayed.yml');
        foreach ($suiviRows['suivis_delayed'] as $row) {
            $this->loadSuiviDelayed($manager, $row);
        }
        $manager->flush();
    }

    /**
     * @param array<string, mixed> $row
     *
     * @throws \Exception
     */
    public function loadSuiviDelayed(ObjectManager $manager, array $row): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => $row['signalement']]);
        $user = $this->userRepository->findOneBy(['email' => $row['user']]);
        $createdAt = (new \DateTimeImmutable())->modify($row['created_at']);
        $category = SuiviCategory::from($row['suivi_category']);
        $type = SuiviDelayedType::from($row['suivid_delayed_type']);

        $suiviDelayed = new SuiviDelayed();
        $suiviDelayed->setSignalement($signalement);
        $suiviDelayed->setUser($user);
        $suiviDelayed->setCreatedAt($createdAt);
        $suiviDelayed->setSuiviCategory($category);
        $suiviDelayed->setSuiviDelayedType($type);
        $suiviDelayed->setChanges($row['changes'] ?? null);

        $manager->persist($suiviDelayed);
    }

    public function getOrder(): int
    {
        return 26;
    }
}
