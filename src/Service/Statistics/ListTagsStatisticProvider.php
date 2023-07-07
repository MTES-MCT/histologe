<?php

namespace App\Service\Statistics;

use App\Entity\Tag;
use App\Entity\Territory;
use App\Repository\TagRepository;

class ListTagsStatisticProvider
{
    public function __construct(private TagRepository $tagsRepository)
    {
    }

    public function getData(?Territory $territory)
    {
        $data = [];
        if (null !== $territory) {
            $tagList = $this->tagsRepository->findAllActive($territory);
            /** @var Tag $tagItem */
            foreach ($tagList as $tagItem) {
                $data[$tagItem->getId()] = $tagItem->getLabel();
            }
        }

        return $data;
    }
}
