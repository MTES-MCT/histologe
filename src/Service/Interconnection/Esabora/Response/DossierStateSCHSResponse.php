<?php

namespace App\Service\Interconnection\Esabora\Response;

class DossierStateSCHSResponse implements DossierResponseInterface
{
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
            $data = $response['rowList'][0]['columnDataList'] ?? null;
            if (null !== $data) {
                $this->sasReference = $data[0] ?? null;
                $this->sasEtat = $data[1] ?? null;
                $this->id = $data[2] ?? null;
                $this->numero = $data[3] ?? null;
                $this->statutAbrege = $data[4] ?? null;
                $this->statut = $data[5] ?? null;
                $this->etat = $data[6] ?? null;
                $this->dateCloture = $data[7] ?? null;
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
