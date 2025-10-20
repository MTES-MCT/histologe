<?php

namespace App\Dto;

use App\Entity\Signalement;
use Symfony\Component\Validator\Constraints as Assert;

class StopProcedure
{
    #[Assert\NotBlank()]
    private ?Signalement $signalement = null;

    private ?string $description = null;

    public function getSignalement(): ?Signalement
    {
        return $this->signalement;
    }

    public function setSignalement(Signalement $signalement): self
    {
        $this->signalement = $signalement;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
