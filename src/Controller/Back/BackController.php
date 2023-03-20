<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Manager\SignalementManager;
use App\Security\Voter\UserVoter;
use App\Service\SearchFilterService;
use App\Service\Signalement\QualificationStatusService;
use App\Service\Signalement\SearchFilterOptionDataProvider;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo')]
class BackController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/signalements/', name: 'back_index')]
    public function index(
        Request $request,
        SearchFilterService $searchFilterService,
        QualificationStatusService $qualificationStatusService,
        SearchFilterOptionDataProvider $searchFilterOptionDataProvider,
        SignalementManager $signalementManager,
    ): Response {
        $title = 'Administration - Tableau de bord';
        $filters = $searchFilterService->setRequest($request)->setFilters()->getFilters();

        /** @var User $user */
        $user = $this->getUser();
        $signalements = $signalementManager->findSignalementAffectationList($user, $filters);

        if ($request->get('pagination')) {
            return $this->stream('back/table_result.html.twig', [
                'filters' => $filters,
                'signalements' => $signalements,
            ]);
        }

        $userSeeNDE = $this->isGranted(UserVoter::SEE_NDE, $user);

        return $this->render('back/index.html.twig', [
            'title' => $title,
            'filters' => $filters,
            'listQualificationStatus' => $qualificationStatusService->getList(),
            'filtersOptionData' => $searchFilterOptionDataProvider->getData($user),
            'countActiveFilters' => $searchFilterService->getCountActive(),
            'displayRefreshAll' => true,
            'signalements' => $signalements,
            'userSeeNDE' => $userSeeNDE,
        ]);
    }
}
