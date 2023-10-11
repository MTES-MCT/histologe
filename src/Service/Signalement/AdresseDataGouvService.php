<?php

namespace App\Service\Signalement;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AdresseDataGouvService
{
    private $apiUrl = 'https://api-adresse.data.gouv.fr/search/?q=';

    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function getCodeInsee(string $address): ?string
    {
        $response = $this->httpClient->request('GET', $this->apiUrl.urlencode($address));

        if (200 === $response->getStatusCode()) {
            $data = $response->toArray();

            if (!empty($data['features'])) {
                $codeInsee = $data['features'][0]['properties']['citycode'];

                return $codeInsee;
            }
        }

        return null;
    }
}
