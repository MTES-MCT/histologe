<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Manager\SignalementManager;
use App\Service\SearchFilterService;
use App\Service\Signalement\SearchFilterOptionDataProvider;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo')]
class SignalementListController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/signalements/', name: 'back_index')]
    public function index(
        Request $request,
        SearchFilterService $searchFilterService,
        SearchFilterOptionDataProvider $searchFilterOptionDataProvider,
        SignalementManager $signalementManager,
        ParameterBagInterface $parameterBag,
    ): Response {
        $filters = $searchFilterService->setRequest($request)->setFilters()->getFilters();
        $request->getSession()->set('filters', $filters);
        /** @var User $user */
        $user = $this->getUser();
        $signalements = $signalementManager->findSignalementAffectationList($user, $filters);

        if ($request->get('pagination')) {
            return $this->stream('back/table_result.html.twig', [
                'filters' => $filters,
                'signalements' => $signalements,
            ]);
        }

        return $this->render('back/index.html.twig', [
            'filters' => $filters,
            'filtersOptionData' => $searchFilterOptionDataProvider->getData($user),
            'countActiveFilters' => $searchFilterService->getCountActive(),
            'displayRefreshAll' => true,
            'signalements' => $signalements,
        ]);
    }
}
