<?php

namespace App\Dto\ServiceSecours;

use App\Entity\Enum\EtageType;
use App\Validator\AdresseOccupantComplete;
use App\Validator\InseeOccupantIsActive;
use App\Validator\NatureLogementAutreRequired;
use Symfony\Component\Validator\Constraints as Assert;

#[InseeOccupantIsActive(groups: ['step2'])]
#[AdresseOccupantComplete(groups: ['step2'])]
#[NatureLogementAutreRequired(groups: ['step2'])]
class FormServiceSecoursStep2
{
    public ?string $adresseCompleteOccupant = null;

    public ?string $adresseOccupant = null;

    public ?string $cpOccupant = null;

    public ?string $villeOccupant = null;

    public ?string $inseeOccupant = null;

    #[Assert\Length(max: 255, groups: ['step2'])]
    public ?string $adresseAutreOccupant = null;

    public ?bool $isLogementSocial = null;

    #[Assert\NotBlank(groups: ['step2'])]
    public ?string $natureLogement = null;

    #[Assert\Length(max: 15, groups: ['step2'])]
    public ?string $natureLogementAutre = null;

    public ?EtageType $typeEtageLogement = null;

    #[Assert\Length(max: 5, groups: ['step2'])]
    public ?string $etageOccupant = null;

    #[Assert\Regex(
        pattern: '/^\d+$/',
        message: 'Merci de saisir un nombre entier.',
        groups: ['step2']
    )]
    public ?string $nbPiecesLogement = null;

    #[Assert\Regex(
        pattern: '/^\d+$/',
        message: 'Merci de saisir un nombre entier.',
        groups: ['step2']
    )]
    public ?string $superficie = null;
}
