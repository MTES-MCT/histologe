<?php

namespace App\Dto\ServiceSecours;

use Symfony\Component\Validator\Constraints as Assert;

class FormServiceSecoursStep5
{
    public const string DESORDRE_AUTRE_SLUG = 'desordres_service_secours_autre';
    public const string DESORDRE_AUTRE_PRECISION_SLUG = 'desordres_service_secours_autre_precision';

    /** @var array<string> */
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
                max: 5000,
                maxMessage: 'Le texte ne doit pas dépasser {{ limit }} caractères.',
                groups: ['step5']
            ),
        ],
        groups: ['step5']
    )]
    public ?string $desordresAutre = null;

    public ?string $autresOccupantsDesordre = null;

    public array $uploadedFiles = [];

    public function hasDesordreAutre(): bool
    {
        return in_array(self::DESORDRE_AUTRE_SLUG, $this->desordres, true);
    }
}
