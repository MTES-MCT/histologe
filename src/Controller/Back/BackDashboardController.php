<?php

namespace App\Controller\Back;

use App\Entity\Territory;
use App\Entity\User;
use App\Repository\TerritoryRepository;
use App\Service\Statistics\ListTerritoryStatisticProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo')]
class BackDashboardController extends AbstractController
{
    private array $ajaxResult;

    public function __construct(
        private ListTerritoryStatisticProvider $listTerritoryStatisticProvider,
        ) {
    }

    #[Route('/', name: 'back_dashboard')]
    public function index(): Response
    {
        $title = 'Tableau de bord';

        return $this->render('back/dashboard/index.html.twig', [
            'title' => $title,
        ]);
    }

    /**
     * Route called by Ajax requests (filters filtered by territory).
     */
    #[Route('/dashboard-filter', name: 'back_dashboard_filter')]
    public function filter(Request $request, TerritoryRepository $territoryRepository): Response
    {
        if ($this->getUser()) {
            $this->ajaxResult = [];

            // Alway return list of territories if Super Admin
            if ($this->isGranted('ROLE_ADMIN')) {
                $this->ajaxResult['list_territoires'] = $this->listTerritoryStatisticProvider->getData();
            }

            $territory = $this->getSelectedTerritory($request, $territoryRepository);

            // TODO : Return global stats

            $this->ajaxResult['response'] = 'success';

            return $this->json($this->ajaxResult);
        }

        return $this->json(['response' => 'error'], 400);
    }

    /**
     * Result for widget Affectations des partenaires.
     */
    #[Route('/dashboard-affectations-partenaires', name: 'back_dashboard_partners')]
    public function dashboardPartners(Request $request, TerritoryRepository $territoryRepository): Response
    {
        if ($this->getUser()) {
            // TODO : Resp. territoire & Super Admin

            $this->ajaxResult['response'] = 'success';

            return $this->json($this->ajaxResult);
        }

        return $this->json(['response' => 'error'], 400);
    }

    /**
     * Result for widget Signalements acceptés mais sans suivi.
     */
    #[Route('/dashboard-signalements-nosuivi', name: 'back_dashboard_signalements_nosuivi')]
    public function dashboardSignalementsNosuivi(Request $request, TerritoryRepository $territoryRepository): Response
    {
        if ($this->getUser()) {
            // TODO : Resp. territoire

            $this->ajaxResult['response'] = 'success';

            return $this->json($this->ajaxResult);
        }

        return $this->json(['response' => 'error'], 400);
    }

    /**
     * Result for widget Signalements sur les territoires.
     */
    #[Route('/dashboard-signalements-par-territoire', name: 'back_dashboard_signalements_per_territoire')]
    public function dashboardSignalementsPerTerritoire(Request $request, TerritoryRepository $territoryRepository): Response
    {
        if ($this->getUser()) {
            // TODO : Super Admin

            $this->ajaxResult['response'] = 'success';

            return $this->json($this->ajaxResult);
        }

        return $this->json(['response' => 'error'], 400);
    }

    /**
     * Result for widget Connexions ESABORA.
     */
    #[Route('/dashboard-connexions-esabora', name: 'back_dashboard_connections_esabora')]
    public function dashboardConnectionsEsabora(Request $request, TerritoryRepository $territoryRepository): Response
    {
        if ($this->getUser()) {
            // TODO : Super Admin

            $this->ajaxResult['response'] = 'success';

            return $this->json($this->ajaxResult);
        }

        return $this->json(['response' => 'error'], 400);
    }

    private function getSelectedTerritory(Request $request, TerritoryRepository $territoryRepository): ?Territory
    {
        // If Super Admin, we should be able to filter on Territoire
        $territory = null;
        if ($this->isGranted('ROLE_ADMIN')) {
            $requestTerritoire = $request->get('territoire');
            if ('' !== $requestTerritoire && 'all' !== $requestTerritoire) {
                $territory = $territoryRepository->findOneBy(['id' => $requestTerritoire]);
            }
        } else {
            /** @var User $user */
            $user = $this->getUser();
            $territory = $user->getTerritory();
        }

        return $territory;
    }
}
