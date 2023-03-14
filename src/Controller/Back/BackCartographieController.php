<?php

namespace App\Controller\Back;

use App\Repository\CritereRepository;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\TagRepository;
use App\Repository\TerritoryRepository;
use App\Security\Voter\UserVoter;
use App\Service\SearchFilterService;
use App\Service\Signalement\QualificationStatusService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/cartographie')]
class BackCartographieController extends AbstractController
{
    public function __construct(
        private SearchFilterService $searchFilterService, )
    {
    }

    #[Route('/', name: 'back_cartographie')]
    public function index(
        SignalementRepository $signalementRepository,
        TagRepository $tagsRepository,
        Request $request,
        CritereRepository $critereRepository,
        TerritoryRepository $territoryRepository,
        PartnerRepository $partnerRepository,
        QualificationStatusService $qualificationStatusService): Response
    {
        $title = 'Cartographie';
        $filters = $this->searchFilterService->setRequest($request)->setFilters()->getFilters();
        $countActiveFilters = $this->searchFilterService->getCountActive();
        if (!$this->isGranted('ROLE_ADMIN_TERRITORY')) {
            $user = $this->getUser();
        }
        if ($request->get('load_markers')) {
            $filters['authorized_codes_insee'] = $this->getParameter('authorized_codes_insee');
            $filters['partner_name'] = $this->getUser()->getPartner()->getNom();

            return $this->json(['signalements' => $signalementRepository->findAllWithGeoData($user ?? null, $filters, (int) $request->get('offset'), $this->getUser()->getTerritory() ?? null)]);
        }

        $userToFilterCities = $this->getUser() ?? null;
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_ADMIN_TERRITORY')) {
            $userToFilterCities = null;
        }

        $userSeeNDE = $this->isGranted(UserVoter::SEE_NDE, $this->getUser());

        return $this->render('back/cartographie/index.html.twig', [
            'title' => $title,
            'filters' => $filters,
            'countActiveFilters' => $countActiveFilters,
            'listQualificationStatus' => $qualificationStatusService->getList(),
            'displayRefreshAll' => false,
            'territories' => $territoryRepository->findAllList(),
            'cities' => $signalementRepository->findCities($userToFilterCities, $this->getUser()->getTerritory() ?? null),
            'partners' => $partnerRepository->findAllList($this->getUser()->getTerritory() ?? null),
            'signalements' => [/* $signalements */],
            'criteres' => $critereRepository->findAllList(),
            'tags' => $tagsRepository->findAllActive($this->getUser()->getTerritory() ?? null),
            'userSeeNDE' => $userSeeNDE,
        ]);
    }
}
