<?php

declare(strict_types=1);

namespace App\Controller\Back;

use App\Entity\Territory;
use App\Entity\User;
use App\Repository\TerritoryRepository;
use App\Service\ClubEventService;
use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyLoaderCollection;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo')]
class DashboardTabPanelController extends AbstractController
{
    public function __construct(private readonly TerritoryRepository $territoryRepository)
    {
    }

    #[Route('/tab-panel-body/{tabBodyType}', name: 'back_tab_panel_body', methods: ['GET'])]
    public function getTabBody(
        string $tabBodyType,
        TabBodyLoaderCollection $tabBodyLoaderCollection,
        #[MapQueryString] TabQueryParameters $tabQueryParameter,
        ClubEventService $clubEventService,
    ): Response {
        /** @var ?User $user */
        $user = $this->getUser();
        $territoires = $this->resolveTerritoires($tabQueryParameter, $user);

        $tab = new TabBody(type: $tabBodyType, territoires: $territoires, tabQueryParameters: $tabQueryParameter);
        $tabBodyLoaderCollection->load($tab);

        return $this->render($tab->getTemplate(), [
            'items' => $tab->getData(),
            'count' => $tab->getCount(),
            'filters' => $tab->getFilters(),
            'nextClubEvent' => $clubEventService->getNextClubEventForUser($user),
        ]);
    }

    /** @return array<int, Territory> */
    private function resolveTerritoires(TabQueryParameters $tabQueryParameter, User $user): array
    {
        $territoires = [];

        $authorizedTerritories = $user->getPartnersTerritories();
        $territoireId = $tabQueryParameter->territoireId;

        if ($territoireId) {
            if ($this->isGranted('ROLE_ADMIN') || isset($authorizedTerritories[$territoireId])) {
                $territory = $this->territoryRepository->find($territoireId);
                if ($territory) {
                    $territoires[(int) $territory->getId()] = $territory;

                    return $territoires;
                }
            }

            $tabQueryParameter->territoireId = $territoireId = null;
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            $territoires = $authorizedTerritories;
            $firstTerritory = current($territoires);
            if (1 === count($territoires) && $firstTerritory instanceof Territory) {
                $tabQueryParameter->territoireId = $firstTerritory->getId();
            } else {
                $tabQueryParameter->territoireId = null;
            }
        }

        return $territoires;
    }
}
