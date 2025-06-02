<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use App\Manager\SuiviManager;
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
        private readonly ParameterBagInterface $parameterBag,
        private readonly SuiviManager $suiviManager,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function load(ObjectManager $manager): void
    {
        $signalements = $this->signalementRepository->findBy(['statut' => [
            SignalementStatus::ACTIVE->value,
            SignalementStatus::CLOSED->value,
        ]]);

        $second = 1;
        foreach ($signalements as $signalement) {
            $suivi = $this->suiviManager->createSuivi(
                signalement: $signalement,
                description: Suivi::DESCRIPTION_SIGNALEMENT_VALIDE,
                type: Suivi::TYPE_AUTO,
                isPublic: true,
                user: $this->userRepository->findOneBy(['email' => $this->parameterBag->get('user_system_email')]),
                context: Suivi::CONTEXT_SIGNALEMENT_ACCEPTED,
                category: SuiviCategory::SIGNALEMENT_IS_ACTIVE,
                flush: false,
            );
            $createdAtUpdated = $signalement->getCreatedAt()->modify('+'.$second.' second');
            $suivi->setCreatedAt($createdAtUpdated);
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
        $signalement = $this->signalementRepository->findOneBy(['reference' => $row['signalement']]);
        $createdBy = isset($row['created_by']) ? $this->userRepository->findOneBy(['email' => $row['created_by']]) : null;
        $createdAt = new \DateTimeImmutable();
        if (isset($row['created_at'])) {
            $createdAt = new \DateTimeImmutable($row['created_at']);
        } elseif (Suivi::TYPE_USAGER_POST_CLOTURE === $row['type']) {
            $createdAt = $signalement->getClosedAt()->modify('+3 days');
        }
        $context = null;
        if (isset($row['context'])) {
            $context = $row['context'];
        }
        $category = null;
        if (isset($row['category'])) {
            $category = SuiviCategory::from($row['category']);
        }

        $suivi = $this->suiviManager->createSuivi(
            signalement: $signalement,
            description: $row['description'],
            type: $row['type'],
            isPublic: $row['is_public'],
            user: $createdBy,
            createdAt: $createdAt,
            context: $context,
            category: $category,
            flush: false,
        );
        $manager->persist($suivi);
    }

    public function getOrder(): int
    {
        return 14;
    }
}
