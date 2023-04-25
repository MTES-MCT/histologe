<?php

namespace App\Service\Esabora\Response;

class DossierPushSISHResponse implements DossierResponseInterface
{
    private ?int $sasId = null;
    private ?int $statusCode = null;
    private ?string $errorReason = null;

    public function __construct(array $response, ?int $statusCode)
    {
        if (!empty($response)) {
            $data = $response['keyDataList'][0] ?? null;
            if (null !== $data) {
                $this->sasId = $data;
            } else {
                $this->errorReason = json_encode($response);
            }
        }
        $this->statusCode = $statusCode;
    }

    public function getSasId(): ?int
    {
        return $this->sasId;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getErrorReason(): ?string
    {
        return $this->errorReason;
    }

    public function getSasEtat(): ?string
    {
        return null;
    }
}
