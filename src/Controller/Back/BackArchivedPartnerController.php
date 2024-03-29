<?php

namespace App\Controller\Back;

use App\Entity\Partner;
use App\Repository\PartnerRepository;
use App\Repository\TerritoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/partner-archives')]
class BackArchivedPartnerController extends AbstractController
{
    #[Route('/', name: 'back_archived_partner_index', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        Request $request,
        TerritoryRepository $territoryRepository,
        PartnerRepository $partnerRepository
    ): Response {
        $page = $request->get('page') ?? 1;

        $isNoneTerritory = 'none' == $request->get('territory');
        $currentTerritory = $isNoneTerritory ? null : $territoryRepository->find((int) $request->get('territory'));
        $partnerTerms = $request->get('partnerTerms');

        $paginatedArchivedPartners = $partnerRepository->findAllArchivedOrWithoutTerritory(
            territory: $currentTerritory,
            isNoneTerritory: $isNoneTerritory,
            filterTerms: $partnerTerms,
            page: (int) $page
        );

        if ($request->isMethod(Request::METHOD_POST)) {
            $isNoneTerritory = 'none' == $request->request->get('territory');
            $currentTerritory = $territoryRepository->find((int) $request->request->get('territory'));
            $partnerTerms = $request->request->get('bo-filters-partnerterms');

            return $this->redirect($this->generateUrl('back_archived_partner_index', [
                'page' => 1,
                'territory' => $isNoneTerritory ? 'none' : $currentTerritory?->getId(),
                'partnerTerms' => $partnerTerms,
            ]));
        }

        $totalArchivedPartners = \count($paginatedArchivedPartners);

        return $this->render('back/partner_archived/index.html.twig', [
            'isNoneTerritory' => $isNoneTerritory,
            'currentTerritory' => $currentTerritory,
            'partnerTerms' => $partnerTerms,
            'territories' => $territoryRepository->findAllList(),
            'partners' => $paginatedArchivedPartners,
            'total' => $totalArchivedPartners,
            'page' => $page,
            'pages' => (int) ceil($totalArchivedPartners / Partner::MAX_LIST_PAGINATION),
        ]);
    }
}
