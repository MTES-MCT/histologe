<?php

namespace App\DataFixtures\Loader;

use App\Entity\Address;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use LongitudeOne\Spatial\PHP\Types\Geometry\Point;
use Symfony\Component\Yaml\Yaml;

class LoadAddressData extends Fixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $addresses = Yaml::parseFile(__DIR__.'/../Files/Address.yml');
        foreach ($addresses['addresses'] as $row) {
            $this->loadAddress($manager, $row);
        }
        $manager->flush();
    }

    /**
     * @param array<string, mixed> $row
     */
    public function loadAddress(ObjectManager $manager, array $row): void
    {
        $address = (new Address())
            ->setHouseNumber($row['housenumber'])
            ->setStreet($row['street'])
            ->setPostCode($row['postCode'])
            ->setCity($row['city'])
            ->setCityCode($row['cityCode'])
        ;
        if (isset($row['latitude']) && isset($row['longitude'])) {
            $point = new Point($row['latitude'], $row['longitude']);
            $address->setPoint($point);
        }
        $manager->persist($address);
    }

    public function getOrder(): int
    {
        return 1;
    }
}
