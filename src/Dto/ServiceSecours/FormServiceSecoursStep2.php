<?php

namespace App\Dto\ServiceSecours;

use App\Entity\Enum\EtageType;
use Symfony\Component\Validator\Constraints as Assert;

class FormServiceSecoursStep2
{
    public ?string $adresseCompleteOccupant = null;

    #[Assert\NotBlank(groups: ['step2'])]
    public ?string $adresseOccupant = null;

    #[Assert\NotBlank(groups: ['step2'])]
    public ?string $cpOccupant = null;

    #[Assert\NotBlank(groups: ['step2'])]
    public ?string $villeOccupant = null;

    public ?string $inseeOccupant = null;

    #[Assert\Length(max: 255, groups: ['step2'])]
    public ?string $adresseAutreOccupant = null;

    public ?bool $isLogementSocial = null;

    public ?string $natureLogement = null;

    public ?EtageType $typeEtageLogement = null;

    #[Assert\Length(max: 5, groups: ['step2'])]
    public ?string $etageOccupant = null;

    public ?int $nbPiecesLogement = null;

    public ?int $superficie = null;
}
