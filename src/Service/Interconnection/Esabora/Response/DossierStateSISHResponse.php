<?php

namespace App\Service\Interconnection\Esabora\Response;

use App\Service\Interconnection\Esabora\EsaboraSISHService;

class DossierStateSISHResponse implements DossierResponseInterface
{
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
            $data = $response['rowList'][0]['columnDataList'] ?? null;
            if (null !== $data) {
                $this->referenceDossier = $data[0] ?? null;
                $this->sasEtat = $data[1] ?? null;
                $this->sasDateDecision = $data[2] ?? null;
                $this->sasCauseRefus = $data[3] ?? null;
                $this->dossId = $data[4] ?? null;
                $this->dossNum = $data[5] ?? null;
                $this->dossObjet = $data[6] ?? null;
                $this->dossDateCloture = $data[7] ?? null;
                $this->dossStatutAbr = $data[8] ?? null;
                $this->dossStatut = $data[9] ?? null;
                $this->dossEtat = $data[10] ?? null;
                $this->dossTypeCode = $data[11] ?? null;
                $this->dossTypeLib = $data[12] ?? null;
            } else {
                $this->errorReason = json_encode($response);
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
