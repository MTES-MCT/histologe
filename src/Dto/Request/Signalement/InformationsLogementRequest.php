<?php

namespace App\Dto\Request\Signalement;

use Symfony\Component\Validator\Constraints as Assert;

class InformationsLogementRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Merci de définir le type de logement.')]
        private readonly ?string $type = null,
        private readonly ?string $nombrePersonnes = null,
        private readonly ?string $compositionLogementEnfants = null,
        #[Assert\DateTime('Y-m-d')]
        private readonly ?string $bailDpeDateEmmenagement = null,
        private readonly ?string $bailDpeBail = null,
        private readonly ?string $bailDpeEtatDesLieux = null,
        private readonly ?string $bailDpeDpe = null,
    ) {
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getNombrePersonnes(): ?string
    {
        return $this->nombrePersonnes;
    }

    public function getCompositionLogementEnfants(): ?string
    {
        return $this->compositionLogementEnfants;
    }

    public function getBailDpeDateEmmenagement(): ?string
    {
        return $this->bailDpeDateEmmenagement;
    }

    public function getBailDpeBail(): ?string
    {
        return $this->bailDpeBail;
    }

    public function getBailDpeEtatDesLieux(): ?string
    {
        return $this->bailDpeEtatDesLieux;
    }

    public function getBailDpeDpe(): ?string
    {
        return $this->bailDpeDpe;
    }
}
