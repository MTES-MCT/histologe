<?php

namespace App\Controller\Back;

use App\Entity\Signalement;
use App\Form\SearchArchivedSignalementType;
use App\Repository\SignalementRepository;
use App\Service\ListFilters\SearchArchivedSignalement;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/signalements-archives')]
class ArchivedSignalementController extends AbstractController
{
    #[Route('/', name: 'back_archived_signalements_index', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        Request $request,
        SignalementRepository $signalementRepository,
        ParameterBagInterface $parameterBag,
    ): Response {
        $searchArchivedSignalement = new SearchArchivedSignalement($this->getUser());
        $form = $this->createForm(SearchArchivedSignalementType::class, $searchArchivedSignalement);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchArchivedSignalement = new SearchArchivedSignalement($this->getUser());
        }
        $maxListPagination = $parameterBag->get('standard_max_list_pagination');
        $paginatedArchivedSignalementPaginated = $signalementRepository->findFilteredArchivedPaginated($searchArchivedSignalement, $maxListPagination);

        return $this->render('back/signalement_archived/index.html.twig', [
            'form' => $form,
            'searchArchivedSignalement' => $searchArchivedSignalement,
            'signalements' => $paginatedArchivedSignalementPaginated,
            'pages' => (int) ceil($paginatedArchivedSignalementPaginated->count() / $maxListPagination),
        ]);
    }

    #[Route('/{uuid:signalement}/reactiver', name: 'back_archived_signalements_reactiver', methods: 'POST')]
    #[IsGranted('ROLE_ADMIN')]
    public function reactiveSignalement(
        Signalement $signalement,
        Request $request,
        ManagerRegistry $doctrine,
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
