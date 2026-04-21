<?php

namespace App\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;

abstract class AbstractManager implements ManagerInterface
{
    public function __construct(protected ManagerRegistry $managerRegistry, protected string $entityName = '')
    {
    }

    public function save(object $entity): void
    {
        $this->managerRegistry->getManager()->persist($entity);
    }

    public function remove(object $entity): void
    {
        $this->managerRegistry->getManager()->remove($entity);
    }

    /**
     * @param int|string $id
     */
    public function find($id): ?object
    {
        return $this->getRepository()->find($id);
    }

    /**
     * @param array<string, mixed> $criteria
     */
    public function findOneBy(array $criteria): ?object
    {
        return $this->getRepository()->findOneBy($criteria);
    }

    // @phpstan-ignore-next-line
    public function getRepository(): ObjectRepository
    {
        return $this->managerRegistry->getRepository($this->entityName); // @phpstan-ignore-line
    }
}
