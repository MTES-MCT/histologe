<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Repository\CritereRepository;
use App\Repository\SignalementRepository;
use App\Repository\TagRepository;
use App\Service\Signalement\SearchFilter;
use App\Service\Signalement\SearchFilterOptionDataProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/cartographie')]
class CartographieController extends AbstractController
{
    public function __construct(
        private SearchFilter $searchFilter,
        private SearchFilterOptionDataProvider $searchFilterOptionDataProvider,
    ) {
    }

    #[Route('/', name: 'back_cartographie')]
    public function index(
        SignalementRepository $signalementRepository,
        TagRepository $tagsRepository,
        Request $request,
        CritereRepository $critereRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $title = 'Cartographie';
        $filters = $this->searchFilter->setRequest($request)->setFilters($user)->getFilters();
        $countActiveFilters = $this->searchFilter->getCountActive();

        if ($request->get('load_markers')) {
            $filters['authorized_codes_insee'] = $this->getParameter('authorized_codes_insee');

            return $this->json(
                [
                    'signalements' => $signalementRepository->findAllWithGeoData(
                        $user,
                        $filters,
                        (int) $request->get('offset')
                    ), ]
            );
        }

        return $this->render('back/cartographie/index.html.twig', [
            'title' => $title,
            'filters' => $filters,
            'filtersOptionData' => $this->searchFilterOptionDataProvider->getData($user),
            'countActiveFilters' => $countActiveFilters,
            'displayRefreshAll' => false,
            'signalements' => [/* $signalements */],
            'criteres' => $critereRepository->findAllList(),
            'tags' => $tagsRepository->findAllActiveInTerritories($this->isGranted('ROLE_ADMIN') ? [] : $user->getPartnersTerritories()),
        ]);
    }
}
