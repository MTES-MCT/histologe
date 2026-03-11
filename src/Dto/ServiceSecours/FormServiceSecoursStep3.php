<?php

namespace App\Dto\ServiceSecours;

use Symfony\Component\Validator\Constraints as Assert;

class FormServiceSecoursStep3
{
    #[Assert\NotBlank(groups: ['step3'])]
    public ?string $profilOccupant = null;

    #[Assert\Length(max: 50, groups: ['step3'])]
    public ?string $nomOccupant = null;

    #[Assert\Length(max: 50, groups: ['step3'])]
    public ?string $prenomOccupant = null;

    #[Assert\Email(mode: Assert\Email::VALIDATION_MODE_STRICT, groups: ['step3'])]
    #[Assert\Length(max: 255, groups: ['step3'])]
    public ?string $mailOccupant = null;

    #[Assert\Length(max: 128, groups: ['step3'])]
    public ?string $telOccupant = null;

    #[Assert\Regex(
        pattern: '/^\d+$/',
        message: 'Merci de saisir un nombre entier.',
        groups: ['step3']
    )]
    public ?string $nbAdultesDansLogement = null;

    #[Assert\Regex(
        pattern: '/^\d+$/',
        message: 'Merci de saisir un nombre entier.',
        groups: ['step3']
    )]
    public ?string $nbEnfantsDansLogement = null;

    public ?string $isEnfantsMoinsSixAnsDansLogement = null;

    public ?string $autreVulnerabilite = null;
}
