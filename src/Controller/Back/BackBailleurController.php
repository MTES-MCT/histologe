<?php

namespace App\Controller\Back;

use App\Entity\Bailleur;
use App\Form\BailleurType;
use App\Form\SearchBailleurType;
use App\Repository\BailleurRepository;
use App\Service\FormHelper;
use App\Service\ListFilters\SearchBailleur;
use App\Service\MessageHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/bailleur')]
#[IsGranted('ROLE_ADMIN')]
class BackBailleurController extends AbstractController
{
    public function __construct(
        #[Autowire(param: 'standard_max_list_pagination')]
        private readonly int $maxListPagination,
        private readonly BailleurRepository $bailleurRepository,
    ) {
    }

    /**
     * @return array{FormInterface, SearchBailleur, Paginator<Bailleur>}
     */
    private function handleSearch(Request $request, bool $fromSearchParams = false): array
    {
        $searchBailleur = new SearchBailleur();
        $form = $this->createForm(SearchBailleurType::class, $searchBailleur);
        FormHelper::handleFormSubmitFromRequestOrSearchParams($form, $request, $fromSearchParams);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchBailleur = new SearchBailleur();
        }
        /** @var Paginator<Bailleur> $paginatedBailleurs */
        $paginatedBailleurs = $this->bailleurRepository->findFilteredPaginated($searchBailleur, $this->maxListPagination);

        return [$form, $searchBailleur, $paginatedBailleurs];
    }

    #[Route('/', name: 'back_bailleur_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        [$form, $searchBailleur, $paginatedBailleurs] = $this->handleSearch($request);

        return $this->render('back/bailleur/index.html.twig', [
            'form' => $form,
            'searchBailleur' => $searchBailleur,
            'bailleurs' => $paginatedBailleurs,
            'pages' => (int) ceil($paginatedBailleurs->count() / $this->maxListPagination),
        ]);
    }

    #[Route('/editer/{bailleur}', name: 'back_bailleur_edit', methods: ['GET', 'POST'])]
    public function edit(
        Bailleur $bailleur,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $form = $this->createForm(BailleurType::class, $bailleur);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', message: ['title' => 'Modifications enregistrées', 'message' => 'Le bailleur a bien été modifié.']);

            return $this->redirectToRoute('back_bailleur_edit', ['bailleur' => $bailleur->getId()]);
        }

        return $this->render('back/bailleur/edit.html.twig', [
            'form' => $form,
            'bailleur' => $bailleur,
        ]);
    }

    #[Route('/supprimer/{bailleur}', name: 'back_bailleur_delete', methods: ['POST'])]
    public function delete(
        Bailleur $bailleur,
        Request $request,
        EntityManagerInterface $em,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('bailleur_delete', $request->query->get('_token'))) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => false]);
        }
        if ($nb = $bailleur->getSignalements()->count() > 0) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur de suppression', 'message' => 'Le bailleur ne peut pas être supprimé car il est lié à '.$nb.' signalements.'];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => false]);
        }
        foreach ($bailleur->getBailleurTerritories() as $bailleurTerritory) {
            $em->remove($bailleurTerritory);
        }
        $em->remove($bailleur);
        $em->flush();
        $flashMessages[] = ['type' => 'success', 'title' => 'Bailleur supprimé', 'message' => 'Le bailleur a bien été supprimé.'];

        [, $searchBailleur, $paginatedBailleurs] = $this->handleSearch($request, true);
        $tableListResult = $this->renderView('back/bailleur/_table-list-results.html.twig', [
            'searchBailleur' => $searchBailleur,
            'bailleurs' => $paginatedBailleurs,
            'pages' => (int) ceil($paginatedBailleurs->count() / $this->maxListPagination),
        ]);
        $titleListResult = $this->renderView('back/bailleur/_title-list-results.html.twig', [
            'bailleurs' => $paginatedBailleurs,
        ]);
        $htmlTargetContents = [
            ['target' => '#table-list-results', 'content' => $tableListResult],
            ['target' => '#title-list-results', 'content' => $titleListResult],
        ];
        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
    }
}
