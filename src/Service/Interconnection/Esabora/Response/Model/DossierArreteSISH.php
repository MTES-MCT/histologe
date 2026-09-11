<?php

namespace App\Service\Interconnection\Esabora\Response\Model;

class DossierArreteSISH
{
    public const int SAS_LOGICIEL_PROVENANCE = 0;
    public const int REFERENCE_DOSSIER = 1;
    public const int DOSS_NUM = 2;
    public const int ARRETE_DATE = 3;
    public const int ARRETE_NUMERO = 4;
    public const int ARRETE_TYPE = 5;
    public const int ARRETE_MAINLEVEE_DATE = 6;
    public const int ARRETE_MAINLEVEE_NUMERO = 7;
    public const int ARRETE_MODIFICATIF_DATE = 8;
    public const int ARRETE_MODIFICATIF_NUMERO = 9;

    private ?int $arreteId = null;
    private ?string $logicielProvenance = null;
    private ?string $referenceDossier = null;
    private ?string $dossNum = null;
    private ?string $arreteDate = null;
    private ?string $arreteNumero = null;
    private ?string $arreteType = null;
    private ?string $arreteMLDate = null;
    private ?string $arreteMLNumero = null;
    private ?string $arreteModificatifDate = null;
    private ?string $arreteModificatifNumero = null;

    /**
     * @param array<mixed> $item
     */
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
                $this->arreteNumero = $data[self::ARRETE_NUMERO];
                $this->arreteType = $data[self::ARRETE_TYPE];
                $this->arreteMLDate = $data[self::ARRETE_MAINLEVEE_DATE];
                $this->arreteMLNumero = $data[self::ARRETE_MAINLEVEE_NUMERO];
                $this->arreteModificatifDate = $data[self::ARRETE_MODIFICATIF_DATE] ?? null;
                $this->arreteModificatifNumero = $data[self::ARRETE_MODIFICATIF_NUMERO] ?? null;
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

    public function getArreteNumero(): ?string
    {
        return $this->arreteNumero;
    }

    public function getArreteType(): ?string
    {
        return $this->arreteType;
    }

    public function getArreteMLDate(): ?string
    {
        return $this->arreteMLDate;
    }

    public function getArreteMLNumero(): ?string
    {
        return $this->arreteMLNumero;
    }

    public function getArreteModificatifDate(): ?string
    {
        return $this->arreteModificatifDate;
    }

    public function getArreteModificatifNumero(): ?string
    {
        return $this->arreteModificatifNumero;
    }

}
