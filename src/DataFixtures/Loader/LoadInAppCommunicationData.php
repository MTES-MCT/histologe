<?php

namespace App\DataFixtures\Loader;

use App\Entity\InAppCommunication;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use LongitudeOne\Spatial\Exception\InvalidValueException;
use Symfony\Component\Yaml\Yaml;

class LoadInAppCommunicationData extends Fixture implements OrderedFixtureInterface
{
    /**
     * @throws InvalidValueException
     */
    public function load(ObjectManager $manager): void
    {
        $communications = Yaml::parseFile(__DIR__.'/../Files/InAppCommunication.yml');
        foreach ($communications['inAppCommunications'] as $row) {
            $this->loadInAppCommunication($manager, $row);
        }
        $manager->flush();
    }

    /**
     * @param array<string, mixed> $row
     *
     * @throws InvalidValueException
     */
    public function loadInAppCommunication(ObjectManager $manager, array $row): void
    {
        $communication = (new InAppCommunication())->setCommunicationType($row['communicationType']);
        if (isset($row['title'])) {
            $communication->setTitle($row['title']);
        }
        if (isset($row['description'])) {
            $communication->setDescription($row['description']);
        }
        if (isset($row['url'])) {
            $communication->setUrl($row['url']);
        }
        if (isset($row['urlTitle'])) {
            $communication->setUrlTitle($row['urlTitle']);
        }
        if (isset($row['userRoles'])) {
            $communication->setUserRoles($row['userRoles']);
        }
        $manager->persist($communication);
    }

    public function getOrder(): int
    {
        return 2;
    }
}
