<?php

namespace App\Controller\Back;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/signalements-archives')]
class ArchivedSignalementController extends AbstractController
{
    #[Route('/', name: 'back_archived_signalements_index', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        Request $request,
        TerritoryRepository $territoryRepository,
        SignalementRepository $signalementRepository
    ): Response {
        $page = $request->get('page') ?? 1;

        $currentTerritory = $territoryRepository->find((int) $request->get('territory'));
        $referenceTerms = $request->get('referenceTerms');

        $paginatedArchivedSignalements = $signalementRepository->findAllArchived(
            territory: $currentTerritory,
            referenceTerms: $referenceTerms,
            page: (int) $page
        );

        if ($request->isMethod(Request::METHOD_POST)) {
            $currentTerritory = $territoryRepository->find((int) $request->request->get('territory'));
            $referenceTerms = $request->request->get('bo-filters-referenceTerms');

            return $this->redirect($this->generateUrl('back_archived_signalements_index', [
                'page' => 1,
                'territory' => $currentTerritory?->getId(),
                'referenceTerms' => $referenceTerms,
            ]));
        }

        $totalArchivedSignalements = \count($paginatedArchivedSignalements);

        return $this->render('back/signalement_archived/index.html.twig', [
            'currentTerritory' => $currentTerritory,
            'referenceTerms' => $referenceTerms,
            'territories' => $territoryRepository->findAllList(),
            'signalements' => $paginatedArchivedSignalements,
            'total' => $totalArchivedSignalements,
            'page' => $page,
            'pages' => (int) ceil($totalArchivedSignalements / Partner::MAX_LIST_PAGINATION),
        ]);
    }

    #[Route('/{uuid}/reactiver', name: 'back_archived_signalements_reactiver', methods: 'POST')]
    #[IsGranted('ROLE_ADMIN')]
    public function reactiveSignalement(
        Signalement $signalement,
        Request $request,
        ManagerRegistry $doctrine
    ): RedirectResponse {
        if ($this->isCsrfTokenValid('signalement_reactive_'.$signalement->getId(), $request->get('_token'))
        && Signalement::STATUS_ARCHIVED === $signalement->getStatut()) {
            $signalement->setStatut(Signalement::STATUS_ACTIVE);
            $doctrine->getManager()->persist($signalement);
            $doctrine->getManager()->flush();

            return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
        }

        return $this->redirectToRoute('back_archived_signalements_index');
    }
}
