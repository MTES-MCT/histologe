<?php

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\Groups;

class CountSignalement
{
    #[Groups(['widget:read'])]
    private ?array $percentage = null;
    #[Groups(['widget:read'])]
    private ?int $closedByAtLeastOnePartner = null;
    #[Groups(['widget:read'])]
    private ?int $closedAllPartnersRecently = null;
    #[Groups(['widget:read'])]
    private ?int $newNDE = null;
    #[Groups(['widget:read'])]
    private ?int $currentNDE = null;
    #[Groups(['widget:read'])]
    private ?int $affected = null;

    public function __construct(
        #[Groups(['widget:read'])]
        private readonly ?int $total = 0,
        #[Groups(['widget:read'])]
        private readonly ?int $new = null,
        #[Groups(['widget:read'])]
        private readonly ?int $active = null,
        #[Groups(['widget:read'])]
        private readonly ?int $closed = null,
        #[Groups(['widget:read'])]
        private readonly ?int $refused = null,
    ) {
        $this->percentage = [
            'new' => 0 !== $total ? round($new / $total * 100, 1) : 0,
            'active' => 0 !== $total ? round($active / $total * 100, 1) : 0,
            'closed' => 0 !== $total ? round($closed / $total * 100, 1) : 0,
            'refused' => 0 !== $total ? round($refused / $total * 100, 1) : 0,
        ];
    }

    public function getTotal(): ?int
    {
        return $this->total;
    }

    public function getNew(): ?int
    {
        return $this->new;
    }

    public function getActive(): ?int
    {
        return $this->active;
    }

    public function getClosed(): ?int
    {
        return $this->closed;
    }

    public function getRefused(): ?int
    {
        return $this->refused;
    }

    public function getPercentage(): ?array
    {
        return $this->percentage;
    }

    public function getClosedByAtLeastOnePartner(): ?int
    {
        return $this->closedByAtLeastOnePartner;
    }

    public function setClosedByAtLeastOnePartner(?int $closedByAtLeastOnePartner): self
    {
        $this->closedByAtLeastOnePartner = $closedByAtLeastOnePartner;

        return $this;
    }

    public function getAffected(): ?int
    {
        return $this->affected;
    }

    public function setAffected(?int $affected): self
    {
        $this->affected = $affected;

        return $this;
    }

    public function getClosedAllPartnersRecently(): ?int
    {
        return $this->closedAllPartnersRecently;
    }

    public function setClosedAllPartnersRecently(?int $closedAllPartnersRecently): self
    {
        $this->closedAllPartnersRecently = $closedAllPartnersRecently;

        return $this;
    }

    public function getNewNDE(): ?int
    {
        return $this->newNDE;
    }

    public function setNewNDE(?int $newNDE): self
    {
        $this->newNDE = $newNDE;

        return $this;
    }

    public function getCurrentNDE(): ?int
    {
        return $this->currentNDE;
    }

    public function setCurrentNDE(?int $currentNDE): self
    {
        $this->currentNDE = $currentNDE;

        return $this;
    }
}
