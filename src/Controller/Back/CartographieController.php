<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\SignalementSearchQuery;
use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Repository\ZoneRepository;
use App\Service\Signalement\SearchFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/cartographie')]
class CartographieController extends AbstractController
{
    #[Route('/', name: 'back_cartographie')]
    public function index(
    ): Response {
        return $this->render('back/cartographie/index.html.twig');
    }

    #[Route('/signalements/', name: 'back_signalement_carto_json')]
    public function list(
        SignalementRepository $signalementRepository,
        ZoneRepository $zoneRepository,
        SearchFilter $searchFilter,
        Request $request,
        #[MapQueryString] ?SignalementSearchQuery $signalementSearchQuery = null,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $filters = null !== $signalementSearchQuery
            ? $searchFilter->setRequest($signalementSearchQuery)->buildFilters($user)
            : [
                'isImported' => 'oui',
            ];
        $signalements = $signalementRepository->findAllWithGeoData(
            $user,
            $filters,
            (int) $request->get('offset')
        );
        $zoneAreas = [];
        if (!empty($filters['zones'])) {
            $criteria = ['id' => $filters['zones']];
            if (!$this->isGranted('ROLE_ADMIN')) {
                $criteria['territory'] = $user->getPartnersTerritories();
            }
            $zones = $zoneRepository->findBy($criteria);
            foreach ($zones as $zone) {
                $zoneAreas[] = $zone->getArea();
            }
        } elseif (!empty($filters['isZonesDisplayed'])) {
            $criteria = [];
            if (!empty($filters['territories'])) {
                $criteria['territory'] = $filters['territories'];
            } elseif (!$this->isGranted('ROLE_ADMIN')) {
                $criteria['territory'] = $user->getPartnersTerritories();
            }
            $zones = $zoneRepository->findBy($criteria);
            foreach ($zones as $zone) {
                $zoneAreas[] = $zone->getArea();
            }
        }

        return $this->json(
            [
                'list' => $signalements,
                'filters' => $filters,
                'zoneAreas' => $zoneAreas,
            ],
            Response::HTTP_OK,
            ['content-type' => 'application/json'],
            ['groups' => ['signalements:read']]
        );
    }
}
