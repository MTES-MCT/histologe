<?php

namespace App\Controller\Back;

use App\Entity\Territory;
use App\Entity\User;
use App\Factory\SettingsFactory;
use App\Form\SearchDashboardAverifierType;
use App\Repository\TerritoryRepository;
use App\Service\DashboardTabPanel\TabDataManager;
use App\Service\ListFilters\SearchDashboardAverifier;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo')]
class DashboardController extends AbstractController
{
    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     * @throws InvalidArgumentException
     */
    #[Route('/', name: 'back_dashboard')]
    public function index(
        Request $request,
        TerritoryRepository $territoryRepository,
        SettingsFactory $settingsFactory,
        TabDataManager $tabDataManager,
        #[MapQueryParameter('territoireId')] ?string $territoireIdRaw = null,
        #[MapQueryParameter('mesDossiersMessagesUsagers')] ?string $mesDossiersMessagesUsagers = null,
        #[MapQueryParameter('mesDossiersAverifier')] ?string $mesDossiersAverifier = null,
        #[MapQueryParameter('mesDossiersActiviteRecente')] ?string $mesDossiersActiviteRecente = null,
    ): Response {
        $territoireId = (is_numeric($territoireIdRaw) ? (int) $territoireIdRaw : null);

        /** @var User $user */
        $user = $this->getUser();

        if ($user->isUserPartner()
            && (null === $mesDossiersMessagesUsagers || null === $mesDossiersAverifier || null === $mesDossiersActiviteRecente)
        ) {
            return $this->redirectToRoute('back_dashboard', [
                'territoireId' => $territoireId,
                'mesDossiersMessagesUsagers' => $mesDossiersMessagesUsagers ?? '1',
                'mesDossiersAverifier' => $mesDossiersAverifier ?? '1',
                'mesDossiersActiviteRecente' => $mesDossiersActiviteRecente ?? '1',
            ]);
        }

        [$territory, $territories] = $this->resolveTerritoryAndTerritories(
            $user,
            $territoryRepository,
            $territoireId
        );

        $settings = $settingsFactory->createInstanceFrom($user, $territory);
        $searchDashboardAverifier = new SearchDashboardAverifier($user);
        $formSearchAverifier = $this->createForm(SearchDashboardAverifierType::class, $searchDashboardAverifier, [
            'method' => 'GET',
            'territory' => $territory,
            'communesAndCp' => $settings->getCommunes(),
            'mesDossiersAverifier' => $mesDossiersAverifier,
        ]);
        $formSearchAverifier->handleRequest($request);

        if ($formSearchAverifier->isSubmitted() && !$formSearchAverifier->isValid()) {
            $searchDashboardAverifier = new SearchDashboardAverifier($user);
        }

        return $this->render('back/dashboard/index.html.twig', [
            'territoireSelectedId' => $territoireId,
            'settings' => $settings,
            'tab_count_kpi' => $tabDataManager->countDataKpi(
                $territories,
                $territory?->getId(),
                $mesDossiersMessagesUsagers,
                $mesDossiersAverifier,
                $mesDossiersActiviteRecente,
                $searchDashboardAverifier->getQueryCommune(),
                $searchDashboardAverifier->getPartners()->map(fn ($p) => $p->getId())->toArray()
            ),
            'territory' => $territory,
            'mesDossiersMessagesUsagers' => $mesDossiersMessagesUsagers,
            'mesDossiersAverifier' => $mesDossiersAverifier,
            'mesDossiersActiviteRecente' => $mesDossiersActiviteRecente,
            'formSearchAverifier' => $formSearchAverifier,
        ]);
    }

    /**
     * @return array{0: Territory|null, 1: array<int, Territory>}
     */
    private function resolveTerritoryAndTerritories(
        User $user,
        TerritoryRepository $territoryRepository,
        ?int $territoireId,
    ): array {
        $territories = [];
        $authorizedTerritories = $user->getPartnersTerritories();
        $territory = null;

        if ($territoireId) {
            if ($this->isGranted('ROLE_ADMIN') || isset($authorizedTerritories[$territoireId])) {
                $territory = $territoryRepository->find($territoireId);
                if ($territory && $territory->isIsActive()) {
                    $territories[$territory->getId()] = $territory;
                } else {
                    $territory = null;
                }
            }
        }

        if (null === $territory) {
            if ($this->isGranted('ROLE_ADMIN')) {
                $territories = $territoryRepository->findAllList();
            } else {
                $territories = $authorizedTerritories;
            }
            if (1 === \count($territories)) {
                $territory = $user->getFirstTerritory();
            }
        }

        return [$territory, $territories];
    }
}
