<?php

namespace App\Service\Esabora\Response\Model;

class DossierVisiteSISH
{
    private ?int $visiteId = null;
    private ?string $sasLogicielProvenance = null;
    private ?string $referenceDossier = null;
    private ?string $dossNum = null;
    private ?string $visiteDateEnreg = null;
    private ?string $visiteDate = null;
    private ?string $visiteObservations = null;
    private ?string $visiteNum = null;
    private ?string $visiteType = null;
    private ?string $visiteStatut = null;
    private ?string $visiteEtat = null;
    private ?string $visitePar = null;

    public function __construct(array $item)
    {
        if (!empty($item)) {
            $this->visiteId = $item['keyDataList'][1] ?? null;
            $data = $item['columnDataList'] ?? null;
            if (null !== $data) {
                $this->sasLogicielProvenance = $data[0];
                $this->referenceDossier = $data[1];
                $this->dossNum = $data[2];
                $this->visiteDateEnreg = $data[3];
                $this->visiteDate = $data[4];
                $this->visiteObservations = $data[5];
                $this->visiteNum = $data[6];
                $this->visiteType = $data[7];
                $this->visiteStatut = $data[8];
                $this->visiteEtat = $data[9];
                $this->visitePar = $data[10];
            }
        }
    }

    public function getVisiteId(): ?int
    {
        return $this->visiteId;
    }

    public function getSasLogicielProvenance(): ?string
    {
        return $this->sasLogicielProvenance;
    }

    public function getReferenceDossier(): ?string
    {
        return $this->referenceDossier;
    }

    public function getDossNum(): ?string
    {
        return $this->dossNum;
    }

    public function getVisiteDateEnreg(): ?string
    {
        return $this->visiteDateEnreg;
    }

    public function getVisiteDate(): ?string
    {
        return $this->visiteDate;
    }

    public function getVisiteObservations(): ?string
    {
        return $this->visiteObservations;
    }

    public function getVisiteNum(): ?string
    {
        return $this->visiteNum;
    }

    public function getVisiteType(): ?string
    {
        return $this->visiteType;
    }

    public function getVisiteStatut(): ?string
    {
        return $this->visiteStatut;
    }

    public function getVisiteEtat(): ?string
    {
        return $this->visiteEtat;
    }

    public function getVisitePar(): ?string
    {
        return $this->visitePar;
    }
}
