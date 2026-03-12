<?php

namespace App\Dto\ServiceSecours;

use Symfony\Component\Validator\Constraints as Assert;

class FormServiceSecoursStep5
{
    public array $desordres = [];
    public ?string $desordresAutre = null;
    public ?bool $autresOccupantsDesordre = null;

    #[Assert\Count(
        min: 1,
        minMessage: 'Veuillez sélectionner un fichier à télécharger.'
    )]
    public array $uploadedFiles = [];
}
