<?php

namespace App\Service\Esabora;

class DossierResponse
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

    public function __construct(array $response, ?int $statusCode)
    {
        if (!empty($response)) {
            $data = $response['rowList'][0]['columnDataList'] ?? null;
            if (null !== $data) {
                $this->sasReference = $data[0];
                $this->sasEtat = $data[1];
                $this->id = $data[2];
                $this->numero = $data[3];
                $this->statutAbrege = $data[4];
                $this->statut = $data[5];
                $this->etat = $data[6];
                $this->dateCloture = $data[7];
            }
        }
        $this->statusCode = $statusCode;
    }

    public function getSasReference(): ?string
    {
        return $this->sasReference;
    }

    public function setSasReference(?string $sasReference): self
    {
        $this->sasReference = $sasReference;

        return $this;
    }

    public function getSasEtat(): ?string
    {
        return $this->sasEtat;
    }

    public function setSasEtat(?string $sasEtat): self
    {
        $this->sasEtat = $sasEtat;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(?string $numero): self
    {
        $this->numero = $numero;

        return $this;
    }

    public function getStatutAbrege(): ?string
    {
        return $this->statutAbrege;
    }

    public function setStatutAbrege(?string $statutAbrege): self
    {
        $this->statutAbrege = $statutAbrege;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): self
    {
        $this->statut = $statut;

        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(?string $etat): self
    {
        $this->etat = $etat;

        return $this;
    }

    public function getDateCloture(): ?string
    {
        return $this->dateCloture;
    }

    public function setDateCloture(?string $dateCloture): self
    {
        $this->dateCloture = $dateCloture;

        return $this;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function setStatusCode(?int $statusCode): self
    {
        $this->statusCode = $statusCode;

        return $this;
    }
}
