<?php

namespace App\Dto\Request\Signalement;

use Symfony\Component\Validator\Constraints as Assert;

class GenerateFileZipSelectionRequest
{
    /**
     * @var int[]
     */
    #[Assert\Count(min: 1, minMessage: 'Merci de sélectionner au moins une image.')]
    #[Assert\All([
        new Assert\Type('numeric'),
        new Assert\Positive(),
    ])]
    public array $fileIds = [];
}
