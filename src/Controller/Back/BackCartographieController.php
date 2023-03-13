<?php

namespace App\Controller\Back;

use App\Repository\SignalementRepository;
use App\Service\SearchFilterService;
use App\Service\Signalement\SearchFilterOptionDataProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/cartographie')]
class BackCartographieController extends AbstractController
{
    public function __construct(
        private SearchFilterService $searchFilterService,
        private SearchFilterOptionDataProvider $searchFilterOptionDataProvider
    ) {
    }

    #[Route('/', name: 'back_cartographie')]
    public function index(
        SignalementRepository $signalementRepository,
        Request $request,
    ): Response {
        $title = 'Cartographie';
        $filters = $this->searchFilterService->setRequest($request)->setFilters()->getFilters();
        $countActiveFilters = $this->searchFilterService->getCountActive();

        if ($request->get('load_markers')) {
            $filters['authorized_codes_insee'] = $this->getParameter('authorized_codes_insee');
            $filters['partner_name'] = $this->getUser()->getPartner()->getNom();
            $user = !$this->isGranted('ROLE_ADMIN_TERRITORY') ? $this->getUser() : null;

            return $this->json([
                'signalements' => $signalementRepository->findAllWithGeoData(
                    $user ?? null, $filters,
                    (int) $request->get('offset'),
                    $this->getUser()->getTerritory() ?? null), ]
            );
        }

        $userToFilterCities = $this->getUser() ?? null;
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_ADMIN_TERRITORY')) {
            $userToFilterCities = null;
        }

        return $this->render('back/cartographie/index.html.twig', [
            'title' => $title,
            'filters' => $filters,
            'filtersOptionData' => $this->searchFilterOptionDataProvider->getData($userToFilterCities),
            'countActiveFilters' => $countActiveFilters,
            'displayRefreshAll' => false,
            'signalements' => [/* $signalements */],
        ]);
    }
}
