<?php

namespace App\Dto\ServiceSecours;

use App\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;

class FormServiceSecoursStep4
{
    public ?bool $isBailleurAverti = null;

    #[Assert\Length(max: 255, groups: ['step4'])]
    public ?string $denominationBailleur = null;

    #[Assert\Email(mode: Assert\Email::VALIDATION_MODE_STRICT, groups: ['step4'])]
    #[Assert\Length(max: 255, groups: ['step4'])]
    public ?string $mailBailleur = null;

    #[Assert\Length(max: 128, groups: ['step4'])]
    #[AppAssert\TelephoneFormat]
    public ?string $telBailleur = null;
}
