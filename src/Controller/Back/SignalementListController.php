<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\SignalementSearchQuery;
use App\Entity\User;
use App\Manager\SignalementManager;
use App\Service\Signalement\SearchFilter;
use App\Service\Signalement\SearchFilterOptionDataProvider;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
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
        SearchFilter $searchFilter,
        SearchFilterOptionDataProvider $searchFilterOptionDataProvider,
        SignalementManager $signalementManager,
        ParameterBagInterface $parameterBag,
    ): Response {
        $filters = $searchFilter->setRequest($request)->setFilters()->getFilters();
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
            'countActiveFilters' => $searchFilter->getCountActive(),
            'displayRefreshAll' => true,
            'signalements' => $signalements,
        ]);
    }

    #[Route('/list/signalements/', name: 'back_signalement_list_json')]
    public function list(
        SignalementManager $signalementManager,
        SearchFilter $searchFilter,
        #[MapQueryString] ?SignalementSearchQuery $signalementQuery = null,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $filters = null !== $signalementQuery
            ? $searchFilter->setRequest($signalementQuery)->buildFilters()
            : ['maxItemsPerPage' => SignalementSearchQuery::MAX_LIST_PAGINATION];

        $signalements = $signalementManager->findSignalementAffectationList($user, $filters);

        return $this->json($signalements);
    }
}
