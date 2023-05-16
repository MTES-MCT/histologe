<?php

namespace App\Service\Esabora\Response;

use App\Service\Esabora\Enum\EsaboraStatus;
use App\Service\Esabora\Response\Model\DossierVisiteSISH;

class DossierVisiteSISHCollectionResponse implements DossierCollectionResponseInterface
{
    private int $statusCode;
    private string $sasEtat;
    private ?string $errorReason = null;

    /** @var DossierVisiteSISH[] */
    private array $collection = [];

    public function __construct(array $response, int $statusCode)
    {
        if (!empty($response)) {
            foreach ($response['rowList'] as $item) {
                $this->collection[] = new DossierVisiteSISH($item);
            }
        }
        $this->statusCode = $statusCode;
        $this->sasEtat = EsaboraStatus::ESABORA_ACCEPTED->value;
    }

    public function getSasEtat(): string
    {
        return $this->sasEtat;
    }

    public function getStatusCode(): int
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
    public function getCollection(): array
    {
        return $this->collection;
    }
}
