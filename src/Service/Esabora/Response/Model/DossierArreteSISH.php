<?php

namespace App\Service\Esabora\Response\Model;

class DossierArreteSISH
{
    private ?int $arreteId = null;
    private ?string $logicielProvenance = null;
    private ?string $referenceDossier = null;
    private ?string $dossNum = null;
    private ?string $arreteDate = null;
    private ?string $arreteDatePresc = null;
    private ?string $arreteCommentaire = null;
    private ?string $arreteNumero = null;
    private ?string $arreteType = null;
    private ?string $arreteEtat = null;
    private ?string $arreteStatut = null;

    public function __construct(array $item)
    {
        if (!empty($item)) {
            $this->arreteId = $item['keyDataList'][1] ?? null;
            $data = $item['columnDataList'] ?? null;
            if (null !== $data) {
                $this->logicielProvenance = $data[0];
                $this->referenceDossier = $data[1];
                $this->dossNum = $data[2];
                $this->arreteDate = $data[3];
                $this->arreteDatePresc = $data[4];
                $this->arreteCommentaire = $data[5];
                $this->arreteNumero = $data[6];
                $this->arreteType = $data[7];
                $this->arreteEtat = $data[8];
                $this->arreteStatut = $data[9];
            }
        }
    }

    public function getArreteId(): ?int
    {
        return $this->arreteId;
    }

    public function getLogicielProvenance(): ?string
    {
        return $this->logicielProvenance;
    }

    public function getReferenceDossier(): ?string
    {
        return $this->referenceDossier;
    }

    public function getDossNum(): ?string
    {
        return $this->dossNum;
    }

    public function getArreteDate(): ?string
    {
        return $this->arreteDate;
    }

    public function getArreteDatePresc(): ?string
    {
        return $this->arreteDatePresc;
    }

    public function getArreteCommentaire(): ?string
    {
        return $this->arreteCommentaire;
    }

    public function getArreteNumero(): ?string
    {
        return $this->arreteNumero;
    }

    public function getArreteType(): ?string
    {
        return $this->arreteType;
    }

    public function getArreteEtat(): ?string
    {
        return $this->arreteEtat;
    }

    public function getArreteStatut(): ?string
    {
        return $this->arreteStatut;
    }
}
