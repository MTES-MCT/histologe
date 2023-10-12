<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Enum\SignalementDraftStatus;
use App\Entity\SignalementDraft;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadSignalementDraft extends Fixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $signalementRows = Yaml::parseFile(__DIR__.'/../Files/SignalementDraft.yml');
        foreach ($signalementRows['signalements_draft'] as $row) {
            $this->loadSignalementsDraft($manager, $row);
        }

        $manager->flush();
    }

    /**
     * @throws \Exception
     */
    private function loadSignalementsDraft(ObjectManager $manager, array $row)
    {
        $payload = json_decode(file_get_contents(__DIR__.'/../Files/signalement_draft_payload/'.$row['payload']), true);
        $signalementDraft = (new SignalementDraft())
            ->setUuid($row['uuid'])
            ->setPayload($payload)
            ->setAddressComplete($payload['adresse_logement_adresse'])
            ->setEmailDeclarant($row['email_declarant'])
            ->setCurrentStep($payload['currentStep'])
            ->setStatus(SignalementDraftStatus::EN_COURS)
            ->setProfileDeclarant(ProfileDeclarant::from($row['profile_declarant']));

        $manager->persist($signalementDraft);
    }

    public function getOrder(): int
    {
        return 14;
    }
}
