<?php

namespace App\Service\Esabora\Response;

use App\Service\Esabora\Response\Model\DossierArreteSISH;

class DossierArreteSISHCollectionResponse
{
    private ?int $statusCode = null;
    private ?string $errorReason = null;
    private array $dossiersArreteSISH = [];

    public function __construct(array $response, int $statusCode)
    {
        if (!empty($response)) {
            foreach ($response['rowList'] as $item) {
                $this->dossiersArreteSISH[] = new DossierArreteSISH($item);
            }
        }

        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getErrorReason(): ?string
    {
        return $this->errorReason;
    }

    /**
     * @return DossierArreteSISH[]
     */
    public function getDossiersArreteSISH(): array
    {
        return $this->dossiersArreteSISH;
    }
}
