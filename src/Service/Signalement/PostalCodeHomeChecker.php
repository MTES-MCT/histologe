<?php

namespace App\Service\Signalement;

use App\Entity\Territory;
use App\Repository\TerritoryRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PostalCodeHomeChecker
{
    private $apiUrl = 'https://api-adresse.data.gouv.fr/search/?q=';
    public const CORSE_DU_SUD_CODE_DEPARTMENT_2A = '2A';
    public const HAUTE_CORSE_CODE_DEPARTMENT_2B = '2B';
    public const MARTINIQUE_CODE_DEPARTMENT_972 = '972';
    public const LA_REUNION_CODE_DEPARTMENT_974 = '974';

    public function __construct(private readonly TerritoryRepository $territoryRepository, private readonly HttpClientInterface $httpClient)
    {
    }

    public function isActive(string $postalCode, ?string $inseeCode = null): bool
    {
        $territoryItem = $this->territoryRepository->findOneBy([
            'zip' => $this->getZipCode($postalCode),
            'isActive' => 1,
        ]);

        if (!empty($territoryItem)) {
            if (empty($inseeCode)) {
                return true;
            }

            return $this->isAuthorizedInseeCode($territoryItem, $inseeCode);
        }

        return false;
    }

    public function getZipCode(string $postalCode): string
    {
        $zipChunk = substr(trim($postalCode), 0, 3);

        return match ($zipChunk) {
            '200', '201' => self::CORSE_DU_SUD_CODE_DEPARTMENT_2A,
            '202', '206' => self::HAUTE_CORSE_CODE_DEPARTMENT_2B,
            '972' => self::MARTINIQUE_CODE_DEPARTMENT_972,
            '974' => self::LA_REUNION_CODE_DEPARTMENT_974,
            default => substr($postalCode, 0, 2),
        };
    }

    public function isAuthorizedInseeCode(Territory $territory, string $inseeCode): bool
    {
        $authorizedCodesInsee = $territory->getAuthorizedCodesInsee();
        if (empty($authorizedCodesInsee) || 0 == \count($authorizedCodesInsee)) {
            return true;
        }

        if (\in_array($inseeCode, $authorizedCodesInsee)) {
            return true;
        }

        return false;
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
