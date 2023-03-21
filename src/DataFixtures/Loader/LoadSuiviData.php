<?php

namespace App\DataFixtures\Loader;

use App\Entity\Suivi;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadSuiviData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private SignalementRepository $signalementRepository,
        private UserRepository $userRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $suiviRows = Yaml::parseFile(__DIR__.'/../Files/Suivi.yml');
        foreach ($suiviRows['suivis'] as $row) {
            $this->loadSuivi($manager, $row);
        }
        $manager->flush();
    }

    public function loadSuivi(ObjectManager $manager, array $row): void
    {
        $suivi = (new Suivi())
            ->setSignalement($this->signalementRepository->findOneBy(['reference' => $row['signalement']]))
            ->setDescription($row['description'])
            ->setCreatedBy($this->userRepository->findOneBy(['email' => $row['created_by']]))
            ->setIsPublic($row['is_public'])
            ->setCreatedAt(
                isset($row['created_at'])
                    ? new \DateTimeImmutable($row['created_at'])
                    : new \DateTimeImmutable()
            )
            ->setType($row['type']);

        $manager->persist($suivi);
    }

    public function getOrder(): int
    {
        return 10;
    }
}
