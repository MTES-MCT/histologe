<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementDraftStatus;
use App\Entity\SignalementDraft;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadSignalementDraft extends Fixture implements OrderedFixtureInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @throws \Exception
     */
    public function load(ObjectManager $manager): void
    {
        $signalementRows = Yaml::parseFile(__DIR__.'/../Files/SignalementDraft.yml');
        foreach ($signalementRows['signalements_draft'] as $row) {
            $this->loadSignalementsDraft($manager, $row);
        }

        $manager->flush();

        $connection = $this->entityManager->getConnection();
        $sql = 'UPDATE signalement_draft SET created_at = DATE(created_at) - INTERVAL 7 MONTH WHERE status LIKE :status';
        $connection->prepare($sql)->executeQuery(['status' => SignalementDraftStatus::EN_COURS->value]);
    }

    /**
     * @param array<string, mixed> $row
     *
     * @throws \Exception
     */
    private function loadSignalementsDraft(ObjectManager $manager, array $row): void
    {
        $payload = json_decode((string) file_get_contents(__DIR__.'/../Files/signalement_draft_payload/'.$row['payload']), true);
        $signalementDraft = (new SignalementDraft())
            ->setUuid($row['uuid'])
            ->setPayload($payload)
            ->setAddressComplete($payload['adresse_logement_adresse'])
            ->setEmailDeclarant($row['email_declarant'])
            ->setCurrentStep($payload['currentStep'])
            ->setProfileDeclarant(ProfileDeclarant::from($row['profile_declarant']));
        if (isset($row['status'])) {
            $signalementDraft->setStatus(SignalementDraftStatus::from($row['status']));
        } else {
            $signalementDraft->setStatus(SignalementDraftStatus::EN_COURS);
        }

        $manager->persist($signalementDraft);
    }

    public function getOrder(): int
    {
        return 12;
    }
}
