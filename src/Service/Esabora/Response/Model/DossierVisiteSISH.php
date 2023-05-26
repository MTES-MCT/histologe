<?php

namespace App\Service\Esabora\Response\Model;

class DossierVisiteSISH
{
    public const SAS_LOGICIEL_PROVENANCE = 0;
    public const REFERENCE_DOSSIER = 1;
    public const DOSS_NUM = 2;
    public const VISITE_DATE_ENREG = 3;
    public const VISITE_DATE = 4;
    public const VISITE_OBSERVATIONS = 5;
    public const VISITE_NUM = 6;
    public const VISITE_TYPE = 7;
    public const VISITE_STATUT = 8;
    public const VISITE_ETAT = 9;
    public const VISITE_PAR = 10;

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
                $this->sasLogicielProvenance = $data[self::SAS_LOGICIEL_PROVENANCE];
                $this->referenceDossier = $data[self::REFERENCE_DOSSIER];
                $this->dossNum = $data[self::DOSS_NUM];
                $this->visiteDateEnreg = $data[self::VISITE_DATE_ENREG];
                $this->visiteDate = $data[self::VISITE_DATE];
                $this->visiteObservations = $data[self::VISITE_OBSERVATIONS];
                $this->visiteNum = $data[self::VISITE_NUM];
                $this->visiteType = $data[self::VISITE_TYPE];
                $this->visiteStatut = $data[self::VISITE_STATUT];
                $this->visiteEtat = strtolower($data[self::VISITE_ETAT]);
                $this->visitePar = $data[self::VISITE_PAR];
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
