<?php

namespace App\Dto;

use App\Entity\Enum\MotifCloture;
use App\Entity\Signalement;
use Symfony\Component\Validator\Constraints as Assert;

class StopProcedure
{
    #[Assert\NotBlank()]
    private ?Signalement $signalement = null;

    private ?MotifCloture $reason = null;
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

    public function getReason(): ?MotifCloture
    {
        return $this->reason;
    }

    public function setReason(?MotifCloture $reason): self
    {
        $this->reason = $reason;

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
