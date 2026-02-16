<?php

namespace App\Dto\ServiceSecours;

use Symfony\Component\Validator\Constraints as Assert;

class FormServiceSecoursStep2
{
    #[Assert\NotBlank(groups: ['step2'])]
    public ?string $adresseComplete = null;

    #[Assert\Length(max: 255, groups: ['step2'])]
    public ?string $adresseAutreOccupant = null;

    public ?bool $isLogementSocial = null;

    public ?string $natureLogement = null;

    public ?string $typeEtageLogement = null;

    #[Assert\Length(max: 5, groups: ['step2'])]
    public ?string $etageOccupant = null;

    public ?int $nbPiecesLogement = null;

    public ?int $superficie = null;
}
