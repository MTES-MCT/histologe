<?php

namespace App\DataFixtures\Loader;

use App\Entity\Arrete;
use App\Entity\Enum\ArreteCreationSource;
use App\Entity\Enum\ArreteType;
use App\Repository\AddressRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
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
        private readonly SignalementRepository $signalementRepository,
        private readonly UserRepository $userRepository,
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
            ->setArreteType(ArreteType::fromLabel($row['arreteType']))
            ->setSyndic($row['syndic'] ?? null)
            ->setDateMainLevee(isset($row['dateMainLevee']) ? new \DateTimeImmutable($row['dateMainLevee']) : null)
            ->setImportedAt(new \DateTimeImmutable())
            ->setIdentifiantParcellaire($row['identifiantParcellaire'] ?? null)
        ;
        if (isset($row['signalement'])) {
            $signalement = $this->signalementRepository->findOneBy(['reference' => $row['signalement']]);
            $user = $this->userRepository->findOneBy(['email' => $row['user']]);
            $arrete->setSignalement($signalement);
            $arrete->setAddress($signalement->getAddress());
            $arrete->setArreteCreationSource(ArreteCreationSource::from($row['arreteCreationSource']));
            $arrete->setCreatedBy($user);
            $arrete->setAdditionalInformation($row['additionalInformation']);
        } else {
            $arrete->setAddress($this->addresses[$row['address']]);
            $arrete->setArreteCreationSource(ArreteCreationSource::IMPORT);
        }

        $manager->persist($arrete);
    }

    public function getOrder(): int
    {
        return 14;
    }
}
