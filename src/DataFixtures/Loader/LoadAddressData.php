<?php

namespace App\DataFixtures\Loader;

use App\Entity\Address;
use App\Repository\TerritoryRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use LongitudeOne\Spatial\PHP\Types\Geometry\Point;
use Symfony\Component\Yaml\Yaml;

class LoadAddressData extends Fixture implements OrderedFixtureInterface
{
    /** @var array<string, \App\Entity\Territory> */
    private array $territories = [];

    public function __construct(
        private readonly TerritoryRepository $territoryRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $list = $this->territoryRepository->findAll();
        foreach ($list as $territory) {
            $this->territories[$territory->getName()] = $territory;
        }
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
            ->setTerritory($this->territories[$row['territory']])
        ;
        if (isset($row['latitude']) && isset($row['longitude'])) {
            $point = new Point($row['latitude'], $row['longitude']);
            $address->setPoint($point);
        }
        $manager->persist($address);
    }

    public function getOrder(): int
    {
        return 2;
    }
}
