<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\PartnerType;
use App\Entity\Partner;
use App\Repository\TerritoryRepository;
use App\Service\Token\TokenGeneratorInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadPartnerData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private TerritoryRepository $territoryRepository,
        private TokenGeneratorInterface $tokenGenerator
    ) {
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
        $partner = (new Partner())
            ->setNom($row['nom'])
            ->setEmail($row['email'] ?? null)
            ->setIsArchive($row['is_archive']);

        if (isset($row['insee'])) {
            $partner->setInsee(json_decode($row['insee'], true));
        }

        $partner->setTerritory($this->territoryRepository->findOneBy(['name' => $row['territory']]));

        if (isset($row['esabora_url'])) {
            $partner->setEsaboraUrl($row['esabora_url'])->setEsaboraToken($this->tokenGenerator->generateToken());
        }

        if (isset($row['type'])) {
            $partner->setType(PartnerType::from($row['type']));
        }
        $manager->persist($partner);
    }

    public function getOrder(): int
    {
        return 6;
    }
}
