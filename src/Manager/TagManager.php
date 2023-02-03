<?php

namespace App\Manager;

use App\Entity\Tag;
use App\Entity\Territory;
use Doctrine\Persistence\ManagerRegistry;

class TagManager extends AbstractManager
{
    public function __construct(ManagerRegistry $managerRegistry, string $entityName = Tag::class)
    {
        parent::__construct($managerRegistry, $entityName);
    }

    public function createOrGet(Territory $territory, string $label): Tag
    {
        $tag = $this->findOneBy(['territory' => $territory, 'label' => $label]);
        if (null === $tag) {
            $tag = (new Tag())
                ->setLabel($label)
                ->setTerritory($territory);

            $this->save($tag);

            return $tag;
        }

        return $tag;
    }
}
