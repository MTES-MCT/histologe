<?php

namespace App\Service\Esabora\Response;

use App\Entity\Affectation;
use App\Service\Esabora\Response\Model\DossierEventSCHS;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

class DossierEventsSCHSResponse
{
    private Affectation $affectation;
    private int $searchId;
    private string $documentTypeName;
    private array $events;

    public function __construct(ResponseInterface $response, Affectation $affectation)
    {
        $statusCode = $response->getStatusCode();
        if (Response::HTTP_OK !== $statusCode) {
            throw new \Exception('status code : '.$statusCode);
        }
        $dataResponse = $response->toArray();
        if (!isset($dataResponse['searchId']) || !isset($dataResponse['documentTypeList']) || !isset($dataResponse['rowList']) || empty($dataResponse['rowList'])) {
            throw new \Exception('Invalid response');
        }
        $this->affectation = $affectation;
        $this->searchId = $dataResponse['searchId'];
        $this->documentTypeName = reset($dataResponse['documentTypeList']);
        foreach ($dataResponse['rowList'] as $event) {
            $this->events[] = new DossierEventSCHS($event, $this);
        }
    }

    public function getAffectation(): Affectation
    {
        return $this->affectation;
    }

    public function getSearchId(): int
    {
        return $this->searchId;
    }

    public function getDocumentTypeName(): string
    {
        return $this->documentTypeName;
    }

    public function getEvents(): array
    {
        return $this->events;
    }
}
