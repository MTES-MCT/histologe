<?php

namespace App\DataFixtures\Loader;

use App\Entity\ServiceSecoursRoute;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadServiceSecoursRouteData extends Fixture implements OrderedFixtureInterface
{
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
        $manager->persist($serviceSecoursRoute);
    }

    public function getOrder(): int
    {
        return 25;
    }
}
