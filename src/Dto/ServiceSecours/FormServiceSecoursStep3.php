<?php

namespace App\Dto\ServiceSecours;

use App\Validator as AppAssert;
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
    #[AppAssert\TelephoneFormat]
    public ?string $telOccupant = null;

    public ?int $nbAdultesDansLogement = null;

    public ?int $nbEnfantsDansLogement = null;

    public ?bool $isEnfantsMoinsSixAnsDansLogement = null;

    public ?string $autreVulnerabilite = null;
}
