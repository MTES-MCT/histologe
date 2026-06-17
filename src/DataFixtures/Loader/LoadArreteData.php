<?php

namespace App\DataFixtures\Loader;

use App\Entity\Arrete;
use App\Entity\Enum\TypeArrete;
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
     */
    public function loadArrete(ObjectManager $manager, array $row): void
    {
        $arrete = (new Arrete())
            ->setDateArrete(new \DateTimeImmutable($row['dateArrete']))
            ->setTypeArrete(TypeArrete::from($row['typeArrete']))
            ->setSyndic(isset($row['syndic']) ? $row['syndic'] : null)
            ->setAddress($this->addresses[$row['address']])
            ->setMainLevee(isset($row['mainLevee']) ? $row['mainLevee'] : false)
            ->setDateMainLevee(isset($row['dateMainLevee']) ? new \DateTimeImmutable($row['dateMainLevee']) : null)
            ->setImportedAt(new \DateTimeImmutable())
        ;
        $manager->persist($arrete);
    }

    public function getOrder(): int
    {
        return 3;
    }
}
