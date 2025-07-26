<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Factory\WidgetSettingsFactory;
use App\Repository\TerritoryRepository;
use App\Service\DashboardTabPanel\TabDataManager;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
        TerritoryRepository $territoryRepository,
        WidgetSettingsFactory $widgetSettingsFactory,
        TabDataManager $tabDataManager,
        #[Autowire(env: 'FEATURE_NEW_DASHBOARD')] ?int $featureNewDashboard = null,
        #[MapQueryParameter('territoireId')] ?int $territoireId = null,
    ): Response {
        if ($featureNewDashboard) {
            $territories = [];
            /** @var User $user */
            $user = $this->getUser();
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

            return $this->render('back/dashboard/index.html.twig', [
                'territoireSelectedId' => $territoireId,
                'settings' => $widgetSettingsFactory->createInstanceFrom($user, $territory),
                'tab_count_kpi' => $tabDataManager->countDataKpi($territories),
                'territory' => $territory,
            ]);
        }

        return $this->render('back/dashboard/index.html.twig');
    }
}
