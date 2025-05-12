<?php

namespace App\Service\Gouv\Rial\Response;

class InvariantsFiscaux
{
    private ?array $invariantsFiscaux = null;
    private ?int $nbInvariantsFiscaux = null;
    private ?string $firstInvariantFiscal = null;

    public function __construct(?array $data = null)
    {
        // {
        //     "listeIdentifiantsFiscaux": [
        //       "920020145586",
        //       "920020145789"
        //     ]
        //   }
        if (null !== $data && !empty($data['listeIdentifiantsFiscaux'])) {
            $this->invariantsFiscaux = $data['listeIdentifiantsFiscaux'];
            $this->nbInvariantsFiscaux = \count($data['listeIdentifiantsFiscaux']);
            $this->firstInvariantFiscal = $data['listeIdentifiantsFiscaux'][0];
        }
    }

    public function getInvariantsFiscaux(): ?array
    {
        return $this->invariantsFiscaux;
    }

    public function getNbInvariantsFiscaux(): ?int
    {
        return $this->nbInvariantsFiscaux;
    }

    public function getFirstInvariantFiscal(): ?string
    {
        return $this->firstInvariantFiscal;
    }
}
