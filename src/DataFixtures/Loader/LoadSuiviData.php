<?php

namespace App\DataFixtures\Loader;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Yaml\Yaml;

class LoadSuiviData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly UserRepository $userRepository,
        private readonly ParameterBagInterface $parameterBag
    ) {
    }

    /**
     * @throws \Exception
     */
    public function load(ObjectManager $manager): void
    {
        $signalements = $this->signalementRepository->findBy(['statut' => [
            Signalement::STATUS_ACTIVE,
            Signalement::STATUS_CLOSED,
        ]]);

        $second = 1;
        foreach ($signalements as $signalement) {
            $createdAtUpdated = $signalement->getCreatedAt()->modify('+'.$second.' second');
            $suivi = (new Suivi())
                ->setSignalement($signalement)
                ->setType(Suivi::TYPE_AUTO)
                ->setDescription(Suivi::DESCRIPTION_SIGNALEMENT_VALIDE)
                ->setIsPublic(true)
                ->setCreatedBy($this->userRepository->findOneBy(
                    ['email' => $this->parameterBag->get('user_system_email')]
                ))
                ->setCreatedAt($createdAtUpdated);

            $manager->persist($suivi);
            ++$second;
        }
        $manager->flush();

        $suiviRows = Yaml::parseFile(__DIR__.'/../Files/Suivi.yml');
        foreach ($suiviRows['suivis'] as $row) {
            $this->loadSuivi($manager, $row);
        }
        $manager->flush();
    }

    /**
     * @throws \Exception
     */
    public function loadSuivi(ObjectManager $manager, array $row): void
    {
        $suivi = (new Suivi())
            ->setSignalement($this->signalementRepository->findOneBy(['reference' => $row['signalement']]))
            ->setDescription($row['description'])
            ->setIsPublic($row['is_public'])
            ->setCreatedAt(
                isset($row['created_at'])
                    ? new \DateTimeImmutable($row['created_at'])
                    : new \DateTimeImmutable()
            )
            ->setType($row['type']);
        if (isset($row['created_by'])) {
            $suivi->setCreatedBy($this->userRepository->findOneBy(['email' => $row['created_by']]));
        }

        $manager->persist($suivi);
    }

    public function getOrder(): int
    {
        return 13;
    }
}
