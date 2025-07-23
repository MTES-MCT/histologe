<?php

namespace App\Dto;

use App\Entity\Suivi;

class SuiviCategory
{
    public function __construct(
        private readonly Suivi $suivi,
        private readonly string $label,
        private readonly string $labelClass,
        private readonly string $title,
        private readonly string $icon,
        private ?string $description,
    ) {
    }

    public function getSuivi(): Suivi
    {
        return $this->suivi;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getLabelClass(): string
    {
        return $this->labelClass;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
