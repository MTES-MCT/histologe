<?php

namespace App\DataFixtures\Loader;

use App\Entity\Config;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Yaml\Yaml;

class LoadConfigData extends Fixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $configRows = Yaml::parseFile(__DIR__.'/../Files/Config.yml');
        foreach ($configRows['config'] as $row) {
            $this->loadConfig($manager, $row);
        }
        $manager->flush();
    }

    public function loadConfig(ObjectManager $manager, array $row): void
    {
        $faker = Factory::create('fr_FR');
        $config = (new Config())
            ->setNomTerritoire($row['nom_territoire'])
            ->setUrlTerritoire($row['url_territoire'])
            ->setNomDpo($faker->name())
            ->setMailDpo($faker->email())
            ->setNomResponsable($faker->name())
            ->setMailResponsable($faker->email())
            ->setEmailReponse($faker->email())
            ->setAdresseDpo($faker->streetAddress());

        $manager->persist($config);
    }

    public function getOrder(): int
    {
        return 1;
    }
}
