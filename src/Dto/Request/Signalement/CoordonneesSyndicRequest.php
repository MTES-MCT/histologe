<?php

namespace App\Dto\Request\Signalement;

use App\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;

class CoordonneesSyndicRequest implements RequestInterface
{
    public function __construct(
        #[Assert\Length(max: 255, maxMessage: 'La dénomination du syndic ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $denomination = null,
        #[Assert\Length(max: 255, maxMessage: 'Le nom du syndic ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $nom = null,
        #[Email(mode: Email::VALIDATION_MODE_STRICT)]
        #[Assert\Length(max: 255, maxMessage: 'L\'email du syndic ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $mail = null,
        #[AppAssert\TelephoneFormat]
        private readonly ?string $telephone = null,
        #[AppAssert\TelephoneFormat]
        private readonly ?string $telephoneBis = null,
    ) {
    }

    public function getDenomination(): ?string
    {
        return $this->denomination;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function getTelephoneBis(): ?string
    {
        return $this->telephoneBis;
    }
}
