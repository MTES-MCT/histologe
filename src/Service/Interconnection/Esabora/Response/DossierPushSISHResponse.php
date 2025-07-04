<?php

namespace App\Service\Interconnection\Esabora\Response;

use App\Service\Interconnection\Esabora\EsaboraSISHService;

class DossierPushSISHResponse implements DossierResponseInterface
{
    private ?int $sasId = null;
    private ?int $statusCode = null;
    private ?string $errorReason = null;

    /**
     * @param array<mixed> $response
     */
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

    public function getSasCauseRefus(): ?string
    {
        return null;
    }

    public function getDossNum(): ?string
    {
        return null;
    }

    public function getEtat(): ?string
    {
        return null;
    }

    public function getNameSI(): ?string
    {
        return EsaboraSISHService::NAME_SI;
    }
}
