<?php

namespace App\Dto\Request\Signalement;

use Symfony\Component\Validator\Constraints as Assert;

class CoordonneesTiersRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Merci de saisir un nom.')]
        private readonly ?string $nom = null,
        #[Assert\NotBlank(message: 'Merci de saisir un prénom.')]
        private readonly ?string $prenom = null,
        #[Assert\NotBlank(message: 'Merci de saisir un courriel.')]
        private readonly ?string $mail = null,
        private readonly ?string $telephone = null,
        private readonly ?string $lien = null,
        private readonly ?string $structure = null,
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

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function getLien(): ?string
    {
        return $this->lien;
    }

    public function getStructure(): ?string
    {
        return $this->structure;
    }
}
