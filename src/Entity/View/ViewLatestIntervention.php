<?php

namespace App\Entity\View;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(readOnly: true)]
#[ORM\Table(name: 'view_latest_intervention')]
class ViewLatestIntervention
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private ?int $signalementId = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $concludeProcedure = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details = null;

    #[ORM\Column(nullable: true)]
    private ?bool $occupantPresent = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $scheduledAt = null;

    #[ORM\Column(type: 'string')]
    private ?string $status = null;

    #[ORM\Column(type: 'integer')]
    private ?int $nbVisites = null;

    public function getSignalementId(): int
    {
        return $this->signalementId;
    }

    public function getConcludeProcedure(): ?string
    {
        return $this->concludeProcedure;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function getOccupantPresent(): ?bool
    {
        return $this->occupantPresent;
    }

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getNbVisites(): ?int
    {
        return $this->nbVisites;
    }
}
