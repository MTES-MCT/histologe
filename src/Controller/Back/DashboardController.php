<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Factory\WidgetSettingsFactory;
use App\Form\SearchDashboardAverifierType;
use App\Repository\TerritoryRepository;
use App\Service\DashboardTabPanel\TabDataManager;
use App\Service\ListFilters\SearchDashboardAverifier;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
     */
    #[Route('/', name: 'back_dashboard')]
    public function index(
        Request $request,
        TerritoryRepository $territoryRepository,
        WidgetSettingsFactory $widgetSettingsFactory,
        TabDataManager $tabDataManager,
        #[Autowire(env: 'FEATURE_NEW_DASHBOARD')] ?int $featureNewDashboard = null,
        #[MapQueryParameter('territoireId')] ?int $territoireId = null,
        #[MapQueryParameter('mesDossiersMessagesUsagers')] ?string $mesDossiersMessagesUsagers = null,
        #[MapQueryParameter('mesDossiersAverifier')] ?string $mesDossiersAverifier = null,
    ): Response {
        if ($featureNewDashboard) {
            $territories = [];
            /** @var User $user */
            $user = $this->getUser();

            if ($user->isUserPartner() && (null === $mesDossiersMessagesUsagers || null === $mesDossiersAverifier)) {
                return $this->redirectToRoute('back_dashboard', [
                    'territoireId' => $territoireId,
                    'mesDossiersMessagesUsagers' => $mesDossiersMessagesUsagers ?? '1',
                    'mesDossiersAverifier' => $mesDossiersAverifier ?? '1',
                ]);
            }

            $authorizedTerritories = $user->getPartnersTerritories();

            $territory = null;
            if ($territoireId && ($this->isGranted('ROLE_ADMIN') || isset($authorizedTerritories[$territoireId]))) {
                $territory = $territoryRepository->find($territoireId);
                if ($territory) {
                    $territories[$territory->getId()] = $territory;
                }
            } elseif (!$this->isGranted('ROLE_ADMIN')) {
                $territories = $authorizedTerritories;
            }

            $searchDashboardAverifier = new SearchDashboardAverifier($user);
            $formSearchAverifier = $this->createForm(SearchDashboardAverifierType::class, $searchDashboardAverifier, [
                'method' => 'GET',
                'territory' => $territory,
                'mesDossiersAverifier' => $mesDossiersAverifier,
            ]);
            $formSearchAverifier->handleRequest($request);
            if ($formSearchAverifier->isSubmitted() && !$formSearchAverifier->isValid()) {
                $searchDashboardAverifier = new SearchDashboardAverifier($user);
            }

            return $this->render('back/dashboard/index.html.twig', [
                'territoireSelectedId' => $territoireId,
                'settings' => $widgetSettingsFactory->createInstanceFrom($user, $territory),
                'tab_count_kpi' => $tabDataManager->countDataKpi($territories, $territoireId, $mesDossiersMessagesUsagers, $mesDossiersAverifier),
                'territory' => $territory,
                'mesDossiersMessagesUsagers' => $mesDossiersMessagesUsagers,
                'mesDossiersAverifier' => $mesDossiersAverifier,
                'formSearchAverifier' => $formSearchAverifier,
            ]);
        }

        return $this->render('back/dashboard/index.html.twig');
    }
}
