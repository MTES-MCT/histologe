<?php

namespace App\Entity;

use App\Entity\Enum\ProcedureType;
use App\Repository\SignalementProcedureRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SignalementProcedureRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_signalement_procedure_type', columns: ['signalement_id', 'procedure_type'])]
class SignalementProcedure
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'signalementProcedures')]
    #[ORM\JoinColumn(nullable: false)]
    private Signalement $signalement;

    #[ORM\Column(enumType: ProcedureType::class)]
    private ProcedureType $procedureType;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSignalement(): Signalement
    {
        return $this->signalement;
    }

    public function setSignalement(Signalement $signalement): static
    {
        $this->signalement = $signalement;

        return $this;
    }

    public function getProcedureType(): ProcedureType
    {
        return $this->procedureType;
    }

    public function setProcedureType(ProcedureType $procedureType): static
    {
        $this->procedureType = $procedureType;

        return $this;
    }
}
