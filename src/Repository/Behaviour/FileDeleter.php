<?php

namespace App\Repository\Behaviour;

use App\Entity\File;
use Doctrine\ORM\EntityManagerInterface;

class FileDeleter
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function remove(File $entity): void
    {
        $this->entityManager->remove($entity);
    }
}
