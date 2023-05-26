<?php

namespace App\Service\Esabora\Response\Model;

class DossierArreteSISH
{
    public const SAS_LOGICIEL_PROVENANCE = 0;
    public const REFERENCE_DOSSIER = 1;
    public const DOSS_NUM = 2;
    public const ARRETE_DATE = 3;
    public const ARRETE_DATE_PRESC = 4;
    public const ARRETE_COMMENTAIRE = 5;
    public const ARRETE_NUMERO = 6;
    public const ARRETE_TYPE = 7;
    public const ARRETE_ETAT = 8;
    public const ARRETE_STATUT = 9;

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
                $this->logicielProvenance = $data[self::SAS_LOGICIEL_PROVENANCE];
                $this->referenceDossier = $data[self::REFERENCE_DOSSIER];
                $this->dossNum = $data[self::DOSS_NUM];
                $this->arreteDate = $data[self::ARRETE_DATE];
                $this->arreteDatePresc = $data[self::ARRETE_DATE_PRESC];
                $this->arreteCommentaire = $data[self::ARRETE_COMMENTAIRE];
                $this->arreteNumero = $data[self::ARRETE_NUMERO];
                $this->arreteType = $data[self::ARRETE_TYPE];
                $this->arreteEtat = strtolower($data[self::ARRETE_ETAT]);
                $this->arreteStatut = $data[self::ARRETE_STATUT];
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
