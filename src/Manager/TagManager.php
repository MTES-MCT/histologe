<?php

namespace App\Manager;

use App\Entity\Tag;
use App\Entity\Territory;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;

class TagManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TagRepository $tagRepository,
    ) {
    }

    public function createOrGet(Territory $territory, string $label): Tag
    {
        /** @var Tag|null $tag */
        $tag = $this->tagRepository->findOneBy(['territory' => $territory, 'label' => $label, 'isArchive' => false]);
        if (null === $tag) {
            $tag = (new Tag())
                ->setLabel($label)
                ->setTerritory($territory);

            $this->entityManager->persist($tag); // flushed by caller

            return $tag;
        }

        return $tag;
    }
}
