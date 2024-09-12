<?php

namespace App\Service\Esabora\Response\Model;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

class DossierEventFilesSCHS
{
    private string $documentZipContent;

    public function __construct(ResponseInterface $response)
    {
        $statusCode = $response->getStatusCode();
        if (Response::HTTP_OK !== $statusCode) {
            throw new \Exception('status code : '.$statusCode);
        }
        $dataResponse = $response->toArray();
        if (!isset($dataResponse['rowList']) || !isset($dataResponse['rowList'][0]) || !isset($dataResponse['rowList'][0]['documentZipContent'])) {
            throw new \Exception('Invalid response');
        }
        $this->documentZipContent = $dataResponse['rowList'][0]['documentZipContent'];
    }

    public function getDocumentZipContent(): string
    {
        return $this->documentZipContent;
    }
}
