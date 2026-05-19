<?php

namespace App\Service\Interconnection\Esabora\Response;

use App\Service\Interconnection\Esabora\EsaboraSISHService;

class DossierStateSISHResponse implements DossierResponseInterface
{
    public const string COL_REFERENCE_DOSSIER = 'Reference_Dossier';
    public const string COL_SAS_ETAT = 'Sas_Etat';
    public const string COL_SAS_DATE_DECISION = 'Sas_DateDecision';
    public const string COL_SAS_CAUSE_REFUS = 'Sas_CauseRefus';
    public const string COL_SISH_DOSS_ID = 'SISH_DossId';
    public const string COL_SISH_DOSS_NUM = 'SISH_DossNum';
    public const string COL_SISH_DOSS_OBJET = 'SISH_DossObjet';
    public const string COL_SISH_DOSS_DATE_CLOTURE = 'SISH_DossDateCloture';
    public const string COL_SISH_DOSS_STATUT_ABR = 'SISH_DossStatutAbr';
    public const string COL_SISH_DOSS_STATUT = 'SISH_DossStatut';
    public const string COL_SISH_DOSS_ETAT = 'SISH_DossEtat';
    public const string COL_SISH_DOSS_TYPE_CODE = 'SISH_DossTypeCode';
    public const string COL_SISH_DOSS_TYPE_LIB = 'SISH_DossTypeLib';

    private ?string $referenceDossier = null;
    private ?string $sasEtat = null;
    private ?string $sasDateDecision = null;
    private ?string $sasCauseRefus = null;
    private ?string $dossId = null;
    private ?string $dossNum = null;
    private ?string $dossObjet = null;
    private ?string $dossDateCloture = null;
    private ?string $dossStatutAbr = null;
    private ?string $dossStatut = null;
    private ?string $dossEtat = null;
    private ?string $dossTypeCode = null;
    private ?string $dossTypeLib = null;
    private ?int $statusCode = null;
    private ?string $errorReason = null;

    /**
     * @param array<mixed> $response
     */
    public function __construct(array $response, ?int $statusCode)
    {
        if (!empty($response)) {
            $columnList = $response['columnList'] ?? null;
            $valueList = $response['rowList'][0]['columnDataList'] ?? null;

            if (null !== $columnList && null !== $valueList) {
                if (\count($columnList) !== \count($valueList)) {
                    $this->errorReason = 'Nombre de colonnes et de données incohérent';
                } else {
                    $data = array_combine($columnList, $valueList);
                    $this->referenceDossier = $data[self::COL_REFERENCE_DOSSIER] ?? null;
                    $this->sasEtat = $data[self::COL_SAS_ETAT] ?? null;
                    $this->sasDateDecision = $data[self::COL_SAS_DATE_DECISION] ?? null;
                    $this->sasCauseRefus = $data[self::COL_SAS_CAUSE_REFUS] ?? null;
                    $this->dossId = $data[self::COL_SISH_DOSS_ID] ?? null;
                    $this->dossNum = $data[self::COL_SISH_DOSS_NUM] ?? null;
                    $this->dossObjet = $data[self::COL_SISH_DOSS_OBJET] ?? null;
                    $this->dossDateCloture = $data[self::COL_SISH_DOSS_DATE_CLOTURE] ?? null;
                    $this->dossStatutAbr = $data[self::COL_SISH_DOSS_STATUT_ABR] ?? null;
                    $this->dossStatut = $data[self::COL_SISH_DOSS_STATUT] ?? null;
                    $this->dossEtat = $data[self::COL_SISH_DOSS_ETAT] ?? null;
                    $this->dossTypeCode = $data[self::COL_SISH_DOSS_TYPE_CODE] ?? null;
                    $this->dossTypeLib = $data[self::COL_SISH_DOSS_TYPE_LIB] ?? null;
                }
            } else {
                $this->errorReason = (string) json_encode($response);
            }
        }
        $this->statusCode = $statusCode;
    }

    public function getReferenceDossier(): ?string
    {
        return $this->referenceDossier;
    }

    public function getSasEtat(): ?string
    {
        return $this->sasEtat;
    }

    public function getSasDateDecision(): ?string
    {
        return $this->sasDateDecision;
    }

    public function getSasCauseRefus(): ?string
    {
        return $this->sasCauseRefus;
    }

    public function getDossId(): ?string
    {
        return $this->dossId;
    }

    public function getDossNum(): ?string
    {
        return $this->dossNum;
    }

    public function getDossObjet(): ?string
    {
        return $this->dossObjet;
    }

    public function getDossDateCloture(): ?string
    {
        return $this->dossDateCloture;
    }

    public function getDossStatutAbr(): ?string
    {
        return $this->dossStatutAbr;
    }

    public function getDossStatut(): ?string
    {
        return $this->dossStatut;
    }

    public function getDossEtat(): ?string
    {
        return $this->dossEtat;
    }

    public function getDossTypeCode(): ?string
    {
        return $this->dossTypeCode;
    }

    public function getDossTypeLib(): ?string
    {
        return $this->dossTypeLib;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getErrorReason(): ?string
    {
        return $this->errorReason;
    }

    public function getEtat(): ?string
    {
        return $this->getDossEtat();
    }

    public function getNameSI(): ?string
    {
        return EsaboraSISHService::NAME_SI;
    }
}
