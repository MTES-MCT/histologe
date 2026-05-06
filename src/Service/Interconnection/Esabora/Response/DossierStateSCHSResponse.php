<?php

namespace App\Service\Interconnection\Esabora\Response;

class DossierStateSCHSResponse implements DossierResponseInterface
{
    public const string COL_SAS_REFERENCE = 'SAS_Référence';
    public const string COL_SAS_ETAT = 'SAS_Etat';
    public const string COL_DOSS_ID = 'Doss_ID';
    public const string COL_DOSS_NUMERO = 'Doss_Numéro';
    public const string COL_DOSS_STATUT_ABREGE = 'Doss_Statut_Abrégé';
    public const string COL_DOSS_STATUT = 'Doss_Statut';
    public const string COL_DOSS_ETAT = 'Doss_Etat';
    public const string COL_DOSS_CLOTURE = 'Doss_Cloture';

    private ?string $sasReference = null;
    private ?string $sasEtat = null;
    private ?string $id = null;
    private ?string $numero = null;
    private ?string $statutAbrege = null;
    private ?string $statut = null;
    private ?string $etat = null;
    private ?string $dateCloture = null;
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
                    $this->sasReference = $data[self::COL_SAS_REFERENCE] ?? null;
                    $this->sasEtat = $data[self::COL_SAS_ETAT] ?? null;
                    $this->id = $data[self::COL_DOSS_ID] ?? null;
                    $this->numero = $data[self::COL_DOSS_NUMERO] ?? null;
                    $this->statutAbrege = $data[self::COL_DOSS_STATUT_ABREGE] ?? null;
                    $this->statut = $data[self::COL_DOSS_STATUT] ?? null;
                    $this->etat = $data[self::COL_DOSS_ETAT] ?? null;
                    $this->dateCloture = $data[self::COL_DOSS_CLOTURE] ?? null;
                }
            } else {
                $this->errorReason = (string) json_encode($response);
            }
        }
        $this->statusCode = $statusCode;
    }

    public function getSasReference(): ?string
    {
        return $this->sasReference;
    }

    public function getSasEtat(): ?string
    {
        return $this->sasEtat;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function getStatutAbrege(): ?string
    {
        return $this->statutAbrege;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function getDateCloture(): ?string
    {
        return $this->dateCloture;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getErrorReason(): ?string
    {
        return $this->errorReason;
    }

    public function getSasCauseRefus(): ?string
    {
        return null;
    }

    public function getDossNum(): ?string
    {
        return null;
    }

    public function getNameSI(): ?string
    {
        return 'Esabora';
    }
}
