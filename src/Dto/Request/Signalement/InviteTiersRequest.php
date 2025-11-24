<?php

namespace App\Dto\Request\Signalement;

use App\Validator\DateNaissanceValidatorTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Email;

class InviteTiersRequest implements RequestInterface
{
    use DateNaissanceValidatorTrait;

    public function __construct(
        #[Assert\NotBlank(message: 'Merci de saisir un nom.')]
        #[Assert\Length(max: 50, maxMessage: 'Le nom ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $nom = null,
        #[Assert\NotBlank(message: 'Merci de saisir un prénom.')]
        #[Assert\Length(max: 50, maxMessage: 'Le prénom ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $prenom = null,
        #[Assert\NotBlank(message: 'Merci de saisir un courriel.')]
        #[Email(mode: Email::VALIDATION_MODE_STRICT)]
        #[Assert\Length(max: 255, maxMessage: 'L\'email ne doit pas dépasser {{ limit }} caractères.')]
        private readonly ?string $mail = null,
    ) {
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }
}
