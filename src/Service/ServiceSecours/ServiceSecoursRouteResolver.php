<?php

namespace App\Service\ServiceSecours;

use App\Entity\ServiceSecoursRoute;
use App\Exception\ServiceSecours\ServiceSecoursRouteResolutionException;
use App\Repository\ServiceSecoursRouteRepository;
use App\Service\Gouv\Insee\Exception\SireneRateLimitExceededException;
use App\Service\Gouv\Insee\SireneService;
use App\Service\Gouv\ProConnect\Model\ProConnectUser;
use App\Service\Signalement\ZipcodeProvider;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class ServiceSecoursRouteResolver
{
    public function __construct(
        private readonly SireneService $sireneService,
        private readonly ServiceSecoursCategoryResolver $serviceSecoursCategoryResolver,
        private readonly ZipcodeProvider $zipcodeProvider,
        private readonly ServiceSecoursRouteRepository $serviceSecoursRouteRepository,
    ) {
    }

    /**
     * @throws ServiceSecoursRouteResolutionException
     * @throws SireneRateLimitExceededException
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function resolve(ProConnectUser $proConnectUser): ServiceSecoursRoute
    {
        $siret = $proConnectUser->siret;
        if (null === $siret || '' === trim($siret)) {
            throw new ServiceSecoursRouteResolutionException('Le SIRET est manquant dans l\'identité ProConnect.');
        }

        $organizationalUnit = $proConnectUser->organizationalUnit;
        if (null === $organizationalUnit || '' === trim($organizationalUnit)) {
            throw new ServiceSecoursRouteResolutionException('L\'unité organisationnelle (organizational_unit) est manquante dans l\'identité ProConnect.');
        }

        try {
            $siretInformation = $this->sireneService->getSiret($siret);
        } catch (SireneRateLimitExceededException $exception) {
            throw $exception;
        } catch (ClientExceptionInterface|\UnexpectedValueException $exception) {
            throw new ServiceSecoursRouteResolutionException(sprintf('Impossible de récupérer les informations de l\'établissement pour le SIRET "%s".', $siret), previous: $exception);
        }
        $territory = $this->zipcodeProvider->getTerritoryByInseeCode($siretInformation->codeCommune);
        if (null === $territory) {
            throw new ServiceSecoursRouteResolutionException(sprintf('Aucun territoire trouvé pour le code commune INSEE "%s" (SIRET "%s").', $siretInformation->codeCommune, $siret));
        }

        $category = $this->serviceSecoursCategoryResolver->resolve($organizationalUnit);

        $serviceSecoursRoute = $this->serviceSecoursRouteRepository->findOneByTerritoryAndCategory($territory, $category);
        if (null === $serviceSecoursRoute) {
            throw new ServiceSecoursRouteResolutionException(sprintf('Aucune route Service Secours configurée pour le territoire "%s" et la catégorie "%s".', $territory->getName(), $category->value));
        }

        return $serviceSecoursRoute;
    }
}
