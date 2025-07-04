<?php

declare(strict_types=1);

namespace App\Controller\Back;

use App\Entity\User;
use App\Repository\TerritoryRepository;
use App\Service\DashboardTabPanel\TabData;
use App\Service\DashboardTabPanel\TabDataLoaderCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class TabPanelController extends AbstractController
{
    public function __construct(private readonly TerritoryRepository $territoryRepository)
    {
    }

    #[When(env: 'dev')]
    #[When(env: 'test')]
    #[Route('/tab-panel-data/{tabDataType}', name: 'back_tab_panel_data', methods: ['GET'])]
    public function getTabData(
        string $tabDataType,
        TabDataLoaderCollection $tabDataLoaderCollection,
        #[MapQueryParameter('territoire')] ?int $territoireId = null,
    ): Response {
        /** @var ?User $user */
        $user = $this->getUser();
        $territoires = [];
        $authorizedTerritories = $user?->getPartnersTerritories();
        if ($territoireId && ($this->isGranted('ROLE_ADMIN') || isset($authorizedTerritories[$territoireId]))) {
            $territory = $this->territoryRepository->find($territoireId);
            if ($territory) {
                $territoires[$territory->getId()] = $territory;
            }
        } elseif (!$this->isGranted('ROLE_ADMIN')) {
            $territoires = $user?->getPartnersTerritories() ?? [];
        }

        $tab = new TabData(type: $tabDataType, territoires: $territoires);
        $tabDataLoaderCollection->load($tab);

        return $this->render($tab->getTemplate(), [
            'items' => $tab->getData(),
            'count' => count($tab->getData() ?? []),
        ]);
    }
}
