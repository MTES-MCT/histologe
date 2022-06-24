<?php

namespace App\Controller\Back;

use App\Repository\CritereRepository;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\TagRepository;
use App\Service\SearchFilterService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/cartographie')]
class BackCartographieController extends AbstractController
{

    #[Route('/', name: 'back_cartographie')]
    public function index(SignalementRepository $signalementRepository, TagRepository $tagsRepository, Request $request, CritereRepository $critereRepository, PartnerRepository $partnerRepository): Response
    {
        $title = 'Cartographie';
        $searchService = new SearchFilterService();
        $filters = $searchService->setRequest($request)->setFilters()->getFilters();
        if (!$this->isGranted('ROLE_ADMIN_TERRITORY'))
            $user = $this->getUser();
        if ($request->get('load_markers')) {
            /* if ($this->isCsrfTokenValid('load_markers_' . (new \DateTimeImmutable())->format('dmYhi'), $request->headers->get('x-token')))*/
            return $this->json(['signalements' => $signalementRepository->findAllWithGeoData($user ?? null, $filters, (int)$request->get('offset'), $this->getUser()->getTerritory() ?? null)]);
            /* else
                 return $this->json(['error' => 'HSTLG_MAPMRKR_BTK'],400);*/
        }
//        dd($signalements->getQuery()->getResult());
//        $signalements['cities'] = $signalementRepository->findCities($user ?? null);

        return $this->render('back/cartographie/index.html.twig', [
            'title' => $title,
            'filters' => $filters,
            'cities' => $signalementRepository->findCities($this->getUser() ?? null, $this->getUser()->getTerritory() ?? null),
            'partners' => $partnerRepository->findAllList($this->getUser()->getTerritory() ?? null),
            'signalements' => [/*$signalements*/],
            'criteres' => $critereRepository->findAllList(),
            'tags' => $tagsRepository->findAllActive($this->getUser()->getTerritory() ?? null),
        ]);
    }
}