<?php

namespace App\Dto\Request\Signalement;

class CoordonneesFoyerRequest
{
    public function __construct(
        private readonly ?string $nom = null,
        private readonly ?string $prenom = null,
        private readonly ?string $mail = null,
        private readonly ?string $telephone = null,
        private readonly ?string $telephoneBis = null,
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

    public function getTelephoneBis(): ?string
    {
        return $this->telephoneBis;
    }
}
