<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\SignalementSearchQuery;
use App\Entity\User;
use App\Manager\SignalementManager;
use App\Service\Signalement\SearchFilter;
use App\Service\Signalement\SearchFilterOptionDataProvider;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo')]
class SignalementListController extends AbstractController
{
    #[Route('/signalements/', name: 'back_index')]
    public function show(): Response {
        return $this->render('back/signalement/list/index.html.twig');
    }

    #[Route('/list/signalements/', name: 'back_signalement_list_json')]
    public function list(
        SessionInterface $session,
        SignalementManager $signalementManager,
        SearchFilter $searchFilter,
        #[MapQueryString] ?SignalementSearchQuery $signalementQuery = null,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $filters = null !== $signalementQuery
            ? $searchFilter->setRequest($signalementQuery)->buildFilters()
            : [
                'maxItemsPerPage' => SignalementSearchQuery::MAX_LIST_PAGINATION,
                'orderBy' => 'DESC',
                'sortBy' => 'reference',
            ];

        $session->set('filters', $filters);
        $signalements = $signalementManager->findSignalementAffectationList($user, $filters);

        return $this->json($signalements);
    }
}
