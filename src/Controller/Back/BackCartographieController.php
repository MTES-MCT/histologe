<?php

namespace App\Controller\Back;

use App\Repository\CritereRepository;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\TagRepository;
use App\Repository\TerritoryRepository;
use App\Service\SearchFilterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/cartographie')]
class BackCartographieController extends AbstractController
{
    #[Route('/', name: 'back_cartographie')]
    public function index(SignalementRepository $signalementRepository, TagRepository $tagsRepository, Request $request, CritereRepository $critereRepository, TerritoryRepository $territoryRepository, PartnerRepository $partnerRepository): Response
    {
        $title = 'Cartographie';
        $searchService = new SearchFilterService();
        $filters = $searchService->setRequest($request)->setFilters()->getFilters();
        if (!$this->isGranted('ROLE_ADMIN_TERRITORY')) {
            $user = $this->getUser();
        }
        if ($request->get('load_markers')) {
            $filters['insee_eligible'] = $this->getParameter('insee_eligible');
            $filters['partner_name'] = $this->getUser()->getPartner()->getNom();

            return $this->json(['signalements' => $signalementRepository->findAllWithGeoData($user ?? null, $filters, (int) $request->get('offset'), $this->getUser()->getTerritory() ?? null)]);
        }

        $userToFilterCities = $this->getUser() ?? null;
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_ADMIN_TERRITORY')) {
            $userToFilterCities = null;
        }

        return $this->render('back/cartographie/index.html.twig', [
            'title' => $title,
            'filters' => $filters,
            'territories' => $territoryRepository->findAllList(),
            'cities' => $signalementRepository->findCities($userToFilterCities, $this->getUser()->getTerritory() ?? null),
            'partners' => $partnerRepository->findAllList($this->getUser()->getTerritory() ?? null),
            'signalements' => [/* $signalements */],
            'criteres' => $critereRepository->findAllList(),
            'tags' => $tagsRepository->findAllActive($this->getUser()->getTerritory() ?? null),
        ]);
    }
}
