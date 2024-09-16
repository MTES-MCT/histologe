<?php

namespace App\Service\Oilhi\Model;

readonly class Desordre
{
    public function __construct(
        private ?string $desordre = null,
        private ?string $equipement = null,
        private ?string $risque = null,
        private ?bool $isDanger = null,
        private ?bool $isSurrocupation = null,
        private ?bool $isInsalubrite = null,
    ) {
    }

    public function getDesordre(): ?string
    {
        return $this->desordre;
    }

    public function getEquipement(): ?string
    {
        return $this->equipement;
    }

    public function getRisque(): ?string
    {
        return $this->risque;
    }

    public function getIsDanger(): ?bool
    {
        return $this->isDanger;
    }

    public function getIsSurrocupation(): ?bool
    {
        return $this->isSurrocupation;
    }

    public function getIsInsalubrite(): ?bool
    {
        return $this->isInsalubrite;
    }
}
