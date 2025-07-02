<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Factory\WidgetSettingsFactory;
use App\Repository\TerritoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo')]
class BackDashboardController extends AbstractController
{
    public function __construct(private readonly TerritoryRepository $territoryRepository)
    {
    }

    #[Route('/', name: 'back_dashboard')]
    public function index(
        WidgetSettingsFactory $widgetSettingsFactory,
        #[Autowire(env: 'FEATURE_NEW_DASHBOARD')] ?int $featureNewDashboard = null,
        #[MapQueryParameter('territoire')] ?int $territoireId = null,
    ): Response {
        if ($featureNewDashboard) {
            /** @var User $user */
            $user = $this->getUser();
            $authorizedTerritories = $user->getPartnersTerritories();

            $territory = null;
            if ($territoireId && ($this->isGranted('ROLE_ADMIN') || isset($authorizedTerritories[$territoireId]))) {
                $territory = $this->territoryRepository->find($territoireId);
            }

            return $this->render('back/dashboard/index.html.twig', [
                'territoireSelectedId' => $territoireId,
                'settings' => $widgetSettingsFactory->createInstanceFrom($user, $territory),
            ]);
        }

        return $this->render('back/dashboard/index.html.twig');
    }
}
