<?php

namespace App\Service\Interconnection\Esabora\Response;

use App\Service\Interconnection\Esabora\Response\Model\DossierEventSCHS;

class DossierEventsSCHSCollectionResponse
{
    private int $statusCode;
    private ?int $searchId;
    private ?string $documentTypeName = null;
    private ?string $errorReason = null;

    /** @var DossierEventSCHS[] */
    private array $collection = [];

    /**
     * @param array<mixed> $response
     */
    public function __construct(array $response, int $statusCode)
    {
        if (!empty($response)) {
            $this->searchId = $response['searchId'] ?? null;
            if (isset($response['documentTypeList'])) {
                $this->documentTypeName = reset($response['documentTypeList']);
            }
            if (isset($response['rowList'])) {
                foreach ($response['rowList'] as $item) {
                    $item['searchId'] = $this->searchId;
                    $item['documentTypeName'] = $this->documentTypeName;
                    $this->collection[] = new DossierEventSCHS($item);
                }
            }
        }
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getSearchId(): ?int
    {
        return $this->searchId;
    }

    public function getErrorReason(): ?string
    {
        return $this->errorReason;
    }

    public function getDocumentTypeName(): ?string
    {
        return $this->documentTypeName;
    }

    /**
     * @return array<DossierEventSCHS>
     */
    public function getCollection(): array
    {
        return $this->collection;
    }
}
