<?php

namespace App\DataFixtures\Loader;

use App\Entity\Arrete;
use App\Entity\Enum\ArreteType;
use App\Repository\AddressRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadArreteData extends Fixture implements OrderedFixtureInterface
{
    /** @var array<string, \App\Entity\Address> */
    private array $addresses = [];

    public function __construct(
        private readonly AddressRepository $addressRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $list = $this->addressRepository->findAll();
        foreach ($list as $address) {
            $key = trim($address->getHouseNumber().' '.$address->getStreet().' '.$address->getPostCode().' '.$address->getCity());
            $this->addresses[$key] = $address;
        }

        $addresses = Yaml::parseFile(__DIR__.'/../Files/Arrete.yml');
        foreach ($addresses['arretes'] as $row) {
            $this->loadArrete($manager, $row);
        }
        $manager->flush();
    }

    /**
     * @param array<string, mixed> $row
     *
     * @throws \DateMalformedStringException
     */
    public function loadArrete(ObjectManager $manager, array $row): void
    {
        $arrete = (new Arrete())
            ->setDateArrete(new \DateTimeImmutable($row['dateArrete']))
            ->setTypeArrete(ArreteType::fromLabel($row['typeArrete']))
            ->setSyndic($row['syndic'] ?? null)
            ->setAddress($this->addresses[$row['address']])
            ->setDateMainLevee(isset($row['dateMainLevee']) ? new \DateTimeImmutable($row['dateMainLevee']) : null)
            ->setImportedAt(new \DateTimeImmutable())
            ->setIdentifiantParcellaire($row['identifiantParcellaire'] ?? null)
        ;
        $manager->persist($arrete);
    }

    public function getOrder(): int
    {
        return 3;
    }
}
