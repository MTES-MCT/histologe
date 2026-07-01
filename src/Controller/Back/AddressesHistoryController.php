<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\AddressesHistorySearchQuery;
use App\Entity\User;
use App\Factory\AddressesHistoryListViewFactory;
use App\Repository\Query\SignalementList\SameAddressQuery;
use App\Repository\TerritoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/historique-des-adresses')]
#[IsGranted('ROLE_ADMIN_TERRITORY')]
class AddressesHistoryController extends AbstractController
{
    public function __construct(
        #[Autowire(env: 'FEATURE_HISTO_ADDRESS')]
        private readonly bool $featureAddressesHistory,
    ) {
        if (!$this->featureAddressesHistory) {
            throw $this->createNotFoundException();
        }
    }

    #[Route('/', name: 'back_addresses_history_index')]
    public function index(): Response
    {
        return $this->render('back/addresses-history/index.html.twig');
    }

    #[Route('/list/addresses/', name: 'back_addresses_history_list_json')]
    public function list(
        SameAddressQuery $sameAddressQuery,
        AddressesHistoryListViewFactory $addressesHistoryListViewFactory,
        #[MapQueryString] ?AddressesHistorySearchQuery $addressesHistorySearchQuery = null,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $filters = null !== $addressesHistorySearchQuery
            ? $addressesHistorySearchQuery->getFilters()
            : [
                // 'maxItemsPerPage' => AddressesHistorySearchQuery::MAX_LIST_PAGINATION,
                // 'orderBy' => 'DESC',
                // 'sortBy' => 'reference',
            ];

        $addresses = $sameAddressQuery->findSameAddressFiltered($user, $filters);
        $responseAddresses = [];
        foreach ($addresses as $address) {
            $addressKey = strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $address['adresseOccupant'].' '.$address['cpOccupant'].' '.$address['villeOccupant']));
            if (!isset($responseAddresses[$addressKey])) {
                $responseAddresses[$addressKey] = $addressesHistoryListViewFactory->createInstance(
                    addressOccupant: $address['adresseOccupant'],
                    cpOccupant: $address['cpOccupant'],
                    villeOccupant: $address['villeOccupant'],
                    territoryId: $address['territoryId'],
                    addressForHuman: $address['adresseOccupant'].' '.$address['cpOccupant'].' '.$address['villeOccupant'],
                    communeForHuman: $address['villeOccupant'].' '.$address['cpOccupant'],
                );
            }

            $addressesHistorySignalement = $addressesHistoryListViewFactory->createSignalementInstanceFromSignalementData($address);
            $responseAddresses[$addressKey]->addSignalement($addressesHistorySignalement);
            if ($address['geoloc'] && isset($address['geoloc']['lat'])) {
                $responseAddresses[$addressKey]->setLat($address['geoloc']['lat']);
                $responseAddresses[$addressKey]->setLng($address['geoloc']['lng']);
            }
        }

        $responseData = [
            'filters' => $filters,
            'list' => array_values($responseAddresses),
            'pagination' => [],
            'zoneAreas' => [],
        ];

        $response = $this->json(
            $responseData,
            Response::HTTP_OK,
            ['content-type' => 'application/json'],
            ['groups' => ['signalements:read']]
        );

        // Remove '?' at the start of the string
        $parsableQueryString = null !== $addressesHistorySearchQuery
            ? substr($addressesHistorySearchQuery->getQueryStringForUrl(), 1)
            : '';
        $cookie = Cookie::create(AddressesHistorySearchQuery::COOKIE_NAME)
            ->withValue($parsableQueryString)
            ->withExpires(strtotime('+1 hour'));

        $response->headers->setCookie($cookie);

        return $response;
    }

    #[Route('/proto-carte-facile', name: 'back_addresses_history_carte_facile')]
    public function carteFacile(SameAddressQuery $sameAddressQuery, TerritoryRepository $territoryRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $territories = $user->getPartnersTerritories();
        if ($this->isGranted('ROLE_ADMIN')) {
            $territories = $territoryRepository->findAllList();
        }

        $signalements = $sameAddressQuery->findSameAddressFiltered($user);
        $signalementsByAddress = [];
        foreach ($signalements as $signalement) {
            $addressKey = strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $signalement['adresseOccupant'].' '.$signalement['cpOccupant'].' '.$signalement['villeOccupant']));
            if (!isset($signalementsByAddress[$addressKey])) {
                $signalementsByAddress[$addressKey] = [
                    'adresse' => $signalement['adresseOccupant'],
                    'cp' => $signalement['cpOccupant'],
                    'ville' => $signalement['villeOccupant'],
                    'territoryId' => $signalement['territoryId'],
                    'addressForHuman' => $signalement['adresseOccupant'].' '.$signalement['cpOccupant'].' '.$signalement['villeOccupant'],
                    'communeForHuman' => $signalement['villeOccupant'].' '.$signalement['cpOccupant'],
                    'lat' => null,
                    'lng' => null,
                    'signalements' => [],
                ];
            }
            $signalementsByAddress[$addressKey]['signalements'][] = $signalement;
            if ($signalement['geoloc']) {
                $signalementsByAddress[$addressKey]['lat'] = $signalement['geoloc']['lat'];
                $signalementsByAddress[$addressKey]['lng'] = $signalement['geoloc']['lng'];
            }
        }

        return $this->render('back/addresses-history/carte-facile.html.twig', [
            'nbSignalements' => count($signalements),
            'signalementsByAddress' => $signalementsByAddress,
            'territories' => $territories,
        ]);
    }

    #[Route('/export', name: 'back_addresses_history_export')]
    public function export(): Response
    {
        // TODO: implémenter l'export
        return new Response('Export en cours de développement');
    }
}
