<?php

namespace App\Dto\Request\Signalement;

use App\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;

class InviteTiersRequest implements RequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Merci de saisir un nom.')]
        #[Assert\Length(max: 50, maxMessage: 'Le nom ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $nom = null,
        #[Assert\NotBlank(message: 'Merci de saisir un prénom.')]
        #[Assert\Length(max: 50, maxMessage: 'Le prénom ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $prenom = null,
        #[Assert\NotBlank(message: 'Merci de saisir un e-mail.')]
        #[Email(mode: Email::VALIDATION_MODE_STRICT)]
        #[Assert\Length(max: 255, maxMessage: 'L\'e-mail ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $mail = null,
        #[AppAssert\TelephoneFormat]
        private readonly ?string $telephone = null,
    ) {
    }

    public function getNom(): string
    {
        if (null === $this->nom) {
            throw new \LogicException('InviteTiersRequest nom is not set');
        }

        return $this->nom;
    }

    public function getPrenom(): string
    {
        if (null === $this->prenom) {
            throw new \LogicException('InviteTiersRequest prenom is not set');
        }

        return $this->prenom;
    }

    public function getMail(): string
    {
        if (null === $this->mail) {
            throw new \LogicException('InviteTiersRequest mail is not set');
        }

        return $this->mail;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }
}
