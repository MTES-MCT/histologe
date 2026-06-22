<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Factory\HistoAddressListViewFactory;
use App\Repository\Query\SignalementList\SameAddressQuery;
use App\Repository\TerritoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/historique-des-adresses')]
#[IsGranted('ROLE_ADMIN_TERRITORY')]
class HistoAddressController extends AbstractController
{
    public function __construct(
        #[Autowire(env: 'FEATURE_HISTO_ADDRESS')]
        private readonly bool $featureHistoAddress,
    ) {
        if (!$this->featureHistoAddress) {
            throw $this->createNotFoundException();
        }
    }

    #[Route('/', name: 'back_histo_address_index')]
    public function index(): Response
    {
        return $this->render('back/histo-address/index.html.twig');
    }

    #[Route('/list/addresses/', name: 'back_histo_addresses_list_json')]
    public function list(
        SameAddressQuery $sameAddressQuery,
        HistoAddressListViewFactory $histoAddressListViewFactory,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $signalements = $sameAddressQuery->findSameAddressFiltered($user);
        $addresses = [];
        foreach ($signalements as $signalement) {
            $addressKey = strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $signalement['adresseOccupant'].' '.$signalement['cpOccupant'].' '.$signalement['villeOccupant']));
            if (!isset($addresses[$addressKey])) {
                $addresses[$addressKey] = $histoAddressListViewFactory->createInstance(
                    addressOccupant: $signalement['adresseOccupant'],
                    cpOccupant: $signalement['cpOccupant'],
                    villeOccupant: $signalement['villeOccupant'],
                    territoryId: $signalement['territoryId'],
                    addressForHuman: $signalement['adresseOccupant'].' '.$signalement['cpOccupant'].' '.$signalement['villeOccupant'],
                    communeForHuman: $signalement['villeOccupant'].' '.$signalement['cpOccupant'],
                );
            }

            $histoAddressSignalement = $histoAddressListViewFactory->createSignalementInstanceFromSignalementData($signalement);
            $addresses[$addressKey]->addSignalement($histoAddressSignalement);
            if ($signalement['geoloc']) {
                $addresses[$addressKey]->setLat($signalement['geoloc']['lat']);
                $addresses[$addressKey]->setLng($signalement['geoloc']['lng']);
            }
        }

        $response = $this->json(
            $addresses,
            Response::HTTP_OK,
            ['content-type' => 'application/json'],
            ['groups' => ['signalements:read']]
        );

        /*
        // TODO: A gérer plus tard pour l'export
        // Remove '?' at the start of the string
        $parsableQueryString = null !== $signalementSearchQuery
            ? substr($signalementSearchQuery->getQueryStringForUrl(), 1)
            : '';
        $cookie = Cookie::create(SignalementSearchQueryFactory::COOKIE_NAME)
            ->withValue($parsableQueryString)
            ->withExpires(strtotime('+1 hour'));

        $response->headers->setCookie($cookie);*/

        return $response;
    }

    #[Route('/proto-carte-facile', name: 'back_histo_address_carte_facile')]
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

        return $this->render('back/histo-address/carte-facile.html.twig', [
            'nbSignalements' => count($signalements),
            'signalementsByAddress' => $signalementsByAddress,
            'territories' => $territories,
        ]);
    }

    #[Route('/export', name: 'back_histo_address_export')]
    public function export(): Response
    {
        // TODO: implémenter l'export
        return new Response('Export en cours de développement');
    }
}
