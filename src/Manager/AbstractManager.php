<?php

namespace App\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;

abstract class AbstractManager implements ManagerInterface
{
    public function __construct(protected ManagerRegistry $managerRegistry, protected string $entityName = '')
    {
    }

    public function save(object $entity, bool $flush = true): void
    {
        $this->managerRegistry->getManager()->persist($entity);
        if ($flush) {
            $this->flush();
        }
    }

    public function remove(object $entity, bool $flush = true): void
    {
        $this->managerRegistry->getManager()->remove($entity);
        if ($flush) {
            $this->flush();
        }
    }

    public function persist(object $entity): void
    {
        $this->save($entity, false);
    }

    public function flush(): void
    {
        $this->managerRegistry->getManager()->flush();
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

    /**
     * @param array<string, mixed> $criteria
     *
     * @return array<int, object>
     */
    public function findBy(array $criteria): array
    {
        return $this->getRepository()->findBy($criteria);
    }

    /**
     * @return array<int, object>
     */
    public function findAll(): array
    {
        return $this->getRepository()->findAll();
    }

    // @phpstan-ignore-next-line
    public function getRepository(): ObjectRepository
    {
        return $this->managerRegistry->getRepository($this->entityName); // @phpstan-ignore-line
    }
}
