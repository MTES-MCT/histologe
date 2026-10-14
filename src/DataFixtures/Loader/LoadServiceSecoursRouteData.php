<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\ServiceSecoursCategory;
use App\Entity\ServiceSecoursRoute;
use App\Repository\TerritoryRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadServiceSecoursRouteData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(private TerritoryRepository $territoryRepository)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $serviceSecoursRoutes = Yaml::parseFile(__DIR__.'/../Files/ServiceSecoursRoute.yml');
        foreach ($serviceSecoursRoutes['service_secours_routes'] as $row) {
            $this->loadServiceSecoursRoute($manager, $row);
        }
        $manager->flush();
    }

    /**
     * @param array<string, mixed> $row
     */
    public function loadServiceSecoursRoute(ObjectManager $manager, array $row): void
    {
        $serviceSecoursRoute = (new ServiceSecoursRoute())->setName($row['name']);
        $serviceSecoursRoute->setEmail($row['email']);
        if (!empty($row['phone'])) {
            $serviceSecoursRoute->setPhone($row['phone']);
        }
        if (!empty($row['category'])) {
            $serviceSecoursRoute->setCategory(ServiceSecoursCategory::from($row['category']));
        }
        $serviceSecoursRoute->setTerritory($this->territoryRepository->findOneBy(['name' => $row['territory']]));
        $manager->persist($serviceSecoursRoute);
    }

    public function getOrder(): int
    {
        return 25;
    }
}
