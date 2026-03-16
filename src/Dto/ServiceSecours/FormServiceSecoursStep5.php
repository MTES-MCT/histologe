<?php

namespace App\Dto\ServiceSecours;

use App\Entity\DesordreCritere;
use Symfony\Component\Validator\Constraints as Assert;

class FormServiceSecoursStep5
{
    public const string DESORDRE_AUTRE = 'desordres_service_secours_autre';

    /** @var array<DesordreCritere> */
    #[Assert\Count(
        min: 1,
        minMessage: 'Veuillez sélectionner au moins un désordre.',
        groups: ['step5']
    )]
    public array $desordres = [];

    #[Assert\When(
        expression: 'this.hasDesordreAutre()',
        constraints: [
            new Assert\NotBlank(
                message: 'Veuillez préciser le désordre autre.',
                groups: ['step5']
            ),
            new Assert\Length(
                max: 2000,
                maxMessage: 'Le texte ne doit pas dépasser {{ limit }} caractères.',
                groups: ['step5']
            ),
        ],
        groups: ['step5']
    )]
    public ?string $desordresAutre = null;

    #[Assert\NotBlank(groups: ['step5'])]
    public ?string $autresOccupantsDesordre = null;

    public array $uploadedFiles = [];

    public function hasDesordreAutre(): bool
    {
        return array_any($this->desordres, fn ($desordre) => self::DESORDRE_AUTRE === $desordre->getSlugCritere());
    }
}
