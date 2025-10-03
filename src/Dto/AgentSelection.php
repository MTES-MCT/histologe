<?php

namespace App\Dto;

use App\Entity\Affectation;
use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

class AgentSelection
{
    #[Assert\NotNull(message: 'Affectation manquante.')]
    private ?Affectation $affectation = null;

    /** @var array<User> */
    #[Assert\Count(min: 1, minMessage: 'Veuillez sélectionner au moins un agent.')]
    private array $agents = [];

    public function getAffectation(): ?Affectation
    {
        return $this->affectation;
    }

    public function setAffectation(Affectation $affectation): self
    {
        $this->affectation = $affectation;

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
