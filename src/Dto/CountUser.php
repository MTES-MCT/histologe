<?php

namespace App\Dto;

use Symfony\Component\Serializer\Attribute\Groups;

class CountUser
{
    #[Groups(['widget:read'])]
    private ?array $percentage = [];

    public function __construct(
        #[Groups(['widget:read'])]
        private readonly ?int $active = null,
        #[Groups(['widget:read'])]
        private readonly ?int $inactive = null,
        #[Groups(['widget:read'])]
        private ?int $archivingScheduled = null,
    ) {
        $total = $this->active + $this->inactive;
        $this->percentage = [
            'active' => 0 !== $total ? round($active / $total * 100, 1) : 0,
            'inactive' => 0 !== $total ? round($inactive / $total * 100, 1) : 0,
        ];
    }

    public function getActive(): ?int
    {
        return $this->active;
    }

    public function getInactive(): ?int
    {
        return $this->inactive;
    }

    public function getPercentage(): ?array
    {
        return $this->percentage;
    }

    public function setArchivingScheduled(?int $archivingScheduled): self
    {
        $this->archivingScheduled = $archivingScheduled;

        return $this;
    }

    public function getArchivingScheduled(): ?int
    {
        return $this->archivingScheduled;
    }
}
