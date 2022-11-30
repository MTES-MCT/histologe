<?php

namespace App\DataFixtures\Loader;

use App\Entity\Partner;
use App\Repository\TerritoryRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Yaml\Yaml;

class LoadPartnerData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(private TerritoryRepository $territoryRepository)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $partnersRows = Yaml::parseFile(__DIR__.'/../Files/Partner.yml');
        foreach ($partnersRows['partners'] as $row) {
            $this->loadPartner($manager, $row);
        }
        $manager->flush();
    }

    public function loadPartner(ObjectManager $manager, array $row): void
    {
        $faker = Factory::create();
        $partner = (new Partner())
            ->setNom($row['nom'])
            ->setEmail($row['email'] ?? null)
            ->setIsArchive($row['is_archive'])
            ->setIsCommune($row['is_commune'])
            ->setInsee(json_decode($row['insee'], true))
            ->setTerritory($this->territoryRepository->findOneBy(['name' => $row['territory']]));

        if (isset($row['esabora_url'])) {
            $partner->setEsaboraUrl($row['esabora_url'])->setEsaboraToken($faker->uuid());
        }

        $manager->persist($partner);
    }

    public function getOrder(): int
    {
        return 6;
    }
}
