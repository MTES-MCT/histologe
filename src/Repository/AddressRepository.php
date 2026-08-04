<?php

namespace App\Repository;

use App\Command\Temp\ManageSignalementAndAddressRelationCommand;
use App\Entity\Address;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Address>
 */
class AddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Address::class);
    }

    /** @return array<string, Address> */
    public function findAllIndexed(): array
    {
        $addresses = $this->findAll();
        $indexedAddresses = [];

        foreach ($addresses as $address) {
            $key = ManageSignalementAndAddressRelationCommand::generateAddressKey(
                $address->getHousenumber(),
                $address->getStreet(),
                $address->getPostCode(),
                $address->getCityCode(),
            );
            $indexedAddresses[$key] = $address;
        }

        return $indexedAddresses;
    }
}
