<?php

namespace App\Dto\ServiceSecours;

use App\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;

class FormServiceSecoursStep4
{
    #[Assert\NotBlank(groups: ['step4'])]
    public ?string $isBailleurAverti = null;

    #[Assert\Length(max: 255, groups: ['step4'])]
    public ?string $denominationProprio = null;

    #[Assert\Length(max: 255, groups: ['step4'])]
    public ?string $nomProprio = null;

    #[Assert\Length(max: 255, groups: ['step4'])]
    public ?string $prenomProprio = null;

    #[Assert\Email(mode: Assert\Email::VALIDATION_MODE_STRICT, groups: ['step4'])]
    #[Assert\Length(max: 255, groups: ['step4'])]
    public ?string $mailProprio = null;

    #[Assert\Length(max: 128, groups: ['step4'])]
    #[AppAssert\TelephoneFormat]
    public ?string $telProprio = null;

    #[Assert\Length(max: 255, groups: ['step4'])]
    public ?string $denominationSyndic = null;

    #[Assert\Length(max: 255, groups: ['step4'])]
    public ?string $nomSyndic = null;

    #[Assert\Email(mode: Assert\Email::VALIDATION_MODE_STRICT, groups: ['step4'])]
    #[Assert\Length(max: 255, groups: ['step4'])]
    public ?string $mailSyndic = null;

    #[Assert\Length(max: 128, groups: ['step4'])]
    #[AppAssert\TelephoneFormat]
    public ?string $telSyndic = null;

    #[Assert\Length(max: 128, groups: ['step4'])]
    #[AppAssert\TelephoneFormat]
    public ?string $telSyndicSecondaire = null;
}
