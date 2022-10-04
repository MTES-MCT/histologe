<?php

namespace App\Manager;

use App\Entity\Tag;
use Doctrine\Persistence\ManagerRegistry;

class TagManager extends AbstractManager
{
    public function __construct(ManagerRegistry $managerRegistry, string $entityName = Tag::class)
    {
        $this->managerRegistry = $managerRegistry;
        $this->entityName = $entityName;
    }
}
