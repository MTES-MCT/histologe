<?php

declare(strict_types=1);

namespace App\Controller\Back;

use App\Entity\Territory;
use App\Entity\User;
use App\Repository\TerritoryRepository;
use App\Service\Metabase\DashboardFilter;
use App\Service\Metabase\DashboardFilterType;
use App\Service\Metabase\DashboardKey;
use App\Service\Metabase\DashboardUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/statistiques-beta')]
class MetabaseController extends AbstractController
{
    #[Route('/', name: 'back_indicateur_pnlhi', methods: ['GET'])]
    public function index(
        Request $request,
        DashboardUrlGenerator $dashboardUrlGenerator,
        TerritoryRepository $territoryRepository,
        #[Autowire(env: 'FEATURE_METABASE_STATS_ENABLE')]
        bool $featureMetabaseStats,
    ): Response {
        if (!$featureMetabaseStats) {
            throw $this->createNotFoundException();
        }

        /** @var User $user */
        $user = $this->getUser();
        $filter = new DashboardFilter($user);
        $form = $this->createForm(DashboardFilterType::class, $filter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && !$form->isValid()) {
            $filter = new DashboardFilter($user);
        }

        /** @var Territory|null $selectedTerritory */
        $selectedTerritory = $filter->getTerritory();
        $authorizedTerritories = $user->getPartnersTerritories();
        $territoryNames = [];
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        if ($selectedTerritory instanceof Territory) {
            $isAuthorized = isset($authorizedTerritories[$selectedTerritory->getId()]);
            $territoryNames[] = $isAdmin || $isAuthorized ? $selectedTerritory->getName() : null;
        }

        if (empty($territoryNames)) {
            if ($isAdmin) {
                $territories = $territoryRepository->findBy(['isActive' => true]);
                $territoryNames = array_map(fn (Territory $territory) => $territory->getName(), $territories);
            } elseif ($user->isMultiTerritoire()) {
                $territoryNames = array_map(fn (Territory $territory) => $territory->getName(), array_values($authorizedTerritories));
            } elseif ($firstTerritory = $user->getFirstTerritory()) {
                $territoryNames = [$firstTerritory->getName()];
            }
        }

        $url = $dashboardUrlGenerator->generate(
            DashboardKey::DASHBOARD_BO, [
                'territoire' => $territoryNames,
            ], [
                'tab' => DashboardKey::DASHBOARD_BO->getDefaultTab(),
            ],
        );

        $iframeTitle = $selectedTerritory
            ? sprintf('Statistiques des signalements pour le territoire %s', $selectedTerritory->getZipAndName())
            : 'Statistiques des signalements';

        return $this->render('back/indicateurs_pnlhi/index.html.twig', [
            'form' => $form->createView(),
            'iframeUrl' => $url,
            'iframeTitle' => $iframeTitle,
            'refreshDelayInSeconds' => $dashboardUrlGenerator->getTtlInSeconds(),
        ]);
    }
}
