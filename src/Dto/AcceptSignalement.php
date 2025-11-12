<?php

namespace App\Dto;

use App\Entity\Signalement;
use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

class AcceptSignalement
{
    #[Assert\NotNull(message: 'Signalement manquant.')]
    private ?Signalement $signalement = null;

    /** @var array<User> */
    #[Assert\Count(min: 1, minMessage: 'Veuillez sélectionner au moins un agent.')]
    private array $agents = [];

    public function getSignalement(): ?Signalement
    {
        return $this->signalement;
    }

    public function setSignalement(Signalement $signalement): self
    {
        $this->signalement = $signalement;

        return $this;
    }

    /** @return array<User> */
    public function getAgents(): array
    {
        return $this->agents;
    }

    /**
     * @param array<User> $agents
     */
    public function setAgents(array $agents): self
    {
        $this->agents = $agents;

        return $this;
    }
}
