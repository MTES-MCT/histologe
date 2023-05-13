<?php

namespace App\Service\Esabora\Response;

use App\Service\Esabora\Response\Model\DossierVisiteSISH;

class DossierVisiteSISHCollectionResponse
{
    private ?int $statusCode = null;
    private ?string $errorReason = null;
    private array $dossiersVisiteSISH = [];

    public function __construct(array $response, int $statusCode)
    {
        if (!empty($response)) {
            foreach ($response['rowList'] as $item) {
                $this->dossiersVisiteSISH[] = new DossierVisiteSISH($item);
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
     * @return DossierVisiteSISH[]
     */
    public function getDossiersVisiteSISH(): array
    {
        return $this->dossiersVisiteSISH;
    }
}
