<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\AddressesHistorySearchQuery;
use App\Entity\User;
use App\Factory\AddressesHistoryListViewFactory;
use App\Repository\Query\Address\AddressesHistoryQuery;
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
        AddressesHistoryQuery $addressesHistoryQuery,
        AddressesHistoryListViewFactory $addressesHistoryListViewFactory,
        #[MapQueryString] ?AddressesHistorySearchQuery $addressesHistorySearchQuery = null,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $filters = null !== $addressesHistorySearchQuery
            ? $addressesHistorySearchQuery->getFilters()
            : [
                'maxItemsPerPage' => AddressesHistorySearchQuery::MAX_LIST_PAGINATION,
                // 'orderBy' => 'DESC',
                // 'sortBy' => 'reference',
            ];

        $page = null !== $addressesHistorySearchQuery && null !== $addressesHistorySearchQuery->getPage()
            ? $addressesHistorySearchQuery->getPage()
            : 1;

        $totalAddresses = $addressesHistoryQuery->countAddressesWithHistory($user, $addressesHistorySearchQuery);
        $totalPages = (int) ceil($totalAddresses / AddressesHistorySearchQuery::MAX_LIST_PAGINATION);

        $addresses = $addressesHistoryQuery->findAddressesWithHistory($user, $addressesHistorySearchQuery);
        $responseAddresses = [];
        $processedSignalements = [];
        $processedArretes = [];

        foreach ($addresses as $row) {
            $addressKey = mb_trim(strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $row['housenumber'].' '.$row['street'].' '.$row['postCode'].' '.$row['cityCode'])));

            if (!isset($responseAddresses[$addressKey])) {
                $responseAddresses[$addressKey] = $addressesHistoryListViewFactory->createInstance(
                    addressOccupant: mb_trim($row['housenumber'].' '.$row['street']),
                    cpOccupant: $row['postCode'],
                    villeOccupant: $row['city'],
                    territoryId: $row['territoryId'],
                    addressForHuman: mb_trim($row['housenumber'].' '.$row['street'].' '.$row['postCode'].' '.$row['city']),
                    communeForHuman: $row['city'].' '.$row['postCode'],
                );
                $processedSignalements[$addressKey] = [];
                $processedArretes[$addressKey] = [];

                // Utilise le point de l'entité Address en priorité
                if (!empty($row['point'])) {
                    // Le point est un objet LongitudeOne\Spatial\PHP\Types\Geometry\Point
                    // x = latitude, y = longitude
                    $point = $row['point'];
                    $responseAddresses[$addressKey]->setLat((string) $point->getX());
                    $responseAddresses[$addressKey]->setLng((string) $point->getY());
                }
            }

            // Traitement des signalements (éviter les doublons)
            if (!empty($row['id']) && !in_array($row['id'], $processedSignalements[$addressKey])) {
                $addressesHistorySignalement = $addressesHistoryListViewFactory->createSignalementInstanceFromSignalementData([
                    'signalementUuid' => $row['uuid'],
                    'reference' => $row['reference'],
                    'prenomOccupant' => $row['prenomOccupant'],
                    'nomOccupant' => $row['nomOccupant'],
                    'statut' => $row['statut'],
                    'declarant' => $row['profileDeclarant'],
                ]);
                $responseAddresses[$addressKey]->addSignalement($addressesHistorySignalement);
                $processedSignalements[$addressKey][] = $row['id'];

                if (true === $row['isLogementSocial']) {
                    $responseAddresses[$addressKey]->setHasLogementSocial(true);
                } elseif (false === $row['isLogementSocial']) {
                    $responseAddresses[$addressKey]->setHasLogementPrive(true);
                }

                if (!empty($row['bailleurName'])) {
                    $responseAddresses[$addressKey]->addBailleurName($row['bailleurName']);
                }

                // Fallback : si pas de point dans Address, on utilise geoloc du signalement
                if (!$responseAddresses[$addressKey]->getLat() && $row['geoloc'] && isset($row['geoloc']['lat'])) {
                    $responseAddresses[$addressKey]->setLat($row['geoloc']['lat']);
                    $responseAddresses[$addressKey]->setLng($row['geoloc']['lng']);
                }
            }

            // Traitement des arrêtés (éviter les doublons)
            if (!empty($row['arreteId']) && !in_array($row['arreteId'], $processedArretes[$addressKey])) {
                $responseAddresses[$addressKey]->addArrete([
                    'id' => $row['arreteId'],
                    'dateArrete' => $row['dateArrete'] ? $row['dateArrete']->format('d/m/Y') : null,
                    'arreteType' => $row['arreteType'],
                    'arreteTypeLabel' => $row['arreteType'] ? $row['arreteType']->completeLabel() : null,
                    'dateMainLevee' => $row['dateMainLevee'] ? $row['dateMainLevee']->format('d/m/Y') : null,
                ]);
                $processedArretes[$addressKey][] = $row['arreteId'];
            }
        }

        $responseData = [
            'filters' => $filters,
            'list' => array_values($responseAddresses),
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalAddresses,
            ],
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
    public function carteFacile(
        SameAddressQuery $sameAddressQuery,
        TerritoryRepository $territoryRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $territories = $user->getPartnersTerritories();
        if ($this->isGranted('ROLE_ADMIN')) {
            $territories = $territoryRepository->findAllList();
        }

        $signalements = $sameAddressQuery->findSameAddressFiltered($user);
        $signalementsByAddress = [];
        foreach ($signalements as $signalement) {
            $addressKey = mb_trim(strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $signalement['housenumber'].' '.$signalement['street'].' '.$signalement['postCode'].' '.$signalement['cityCode'])));
            if (!isset($signalementsByAddress[$addressKey])) {
                $signalementsByAddress[$addressKey] = [
                    'adresse' => mb_trim($signalement['housenumber'].' '.$signalement['street']),
                    'cp' => $signalement['postCode'],
                    'ville' => $signalement['city'],
                    'territoryId' => $signalement['territoryId'],
                    'addressForHuman' => mb_trim($signalement['housenumber'].' '.$signalement['street'].' '.$signalement['postCode'].' '.$signalement['city']),
                    'communeForHuman' => mb_trim($signalement['city'].' '.$signalement['postCode']),
                    'lat' => null,
                    'lng' => null,
                    'signalements' => [],
                ];
            }
            $signalementsByAddress[$addressKey]['signalements'][] = $signalement;
            if (!empty($signalement['point'])) {
                $signalementsByAddress[$addressKey]['lat'] = (string) $signalement['point']->getY();
                $signalementsByAddress[$addressKey]['lng'] = (string) $signalement['point']->getX();
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
