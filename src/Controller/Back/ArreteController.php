<?php

namespace App\Controller\Back;

use App\Entity\Arrete;
use App\Entity\User;
use App\Form\ImportArreteType;
use App\Form\SearchArreteType;
use App\Repository\ArreteRepository;
use App\Service\ListFilters\SearchArrete;
use App\Service\MessageHelper;
use App\Utils\FormHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/gerer-territoire/arretes')]
class ArreteController extends AbstractController
{
    public function __construct(
        private readonly ArreteRepository $arreteRepository,
        #[Autowire(param: 'standard_max_list_pagination')]
        private readonly int $maxListPagination,
        #[Autowire(env: 'FEATURE_HISTO_ADDRESS')]
        private readonly bool $featureHistoAddress,
    ) {
        if (!$this->featureHistoAddress) {
            throw $this->createNotFoundException();
        }
    }

    /**
     * @return array{FormInterface<mixed>, SearchArrete, Paginator<Arrete>}
     */
    private function handleSearch(Request $request, bool $fromSearchParams = false): array
    {
        /** @var User $user */
        $user = $this->getUser();
        $searchArrete = new SearchArrete($user);
        $form = $this->createForm(SearchArreteType::class, $searchArrete);
        FormHelper::handleFormSubmitFromRequestOrSearchParams($form, $request, $fromSearchParams);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchArrete = new SearchArrete($user);
        }

        /** @var Paginator<Arrete> $paginatedArretes */
        $paginatedArretes = $this->arreteRepository->findFilteredPaginated($searchArrete, $this->maxListPagination);

        return [$form, $searchArrete, $paginatedArretes];
    }

    #[Route('/', name: 'back_territory_management_arrete_index', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN_TERRITORY')]
    public function index(Request $request): Response
    {
        [$form, $searchArrete, $paginatedArretes] = $this->handleSearch($request);

        return $this->render('back/arrete/index.html.twig', [
            'form' => $form,
            'searchArrete' => $searchArrete,
            'arretes' => $paginatedArretes,
            'pages' => (int) ceil($paginatedArretes->count() / $this->maxListPagination),
        ]);
    }

    #[Route('/import', name: 'back_arrete_import', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN_TERRITORY')]
    public function importCsv(Request $request): Response
    {
        $form = $this->createForm(ImportArreteType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // todo
            $this->addFlash('success', 'Fichier envoyé avec succès.');
        }

        return $this->render('back/arrete/import.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/supprimer/{arrete}', name: 'back_territory_management_arrete_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN_TERRITORY')]
    public function delete(Arrete $arrete, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('arrete_delete', $request->query->get('_token'))) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => false]);
        }

        $entityManager->remove($arrete);
        $entityManager->flush();
        $flashMessages[] = ['type' => 'success', 'title' => 'Arrêté supprimé', 'message' => 'L\'arrêté a bien été supprimé.'];

        [, $searchArrete, $paginatedArretes] = $this->handleSearch($request, true);
        $tableListResult = $this->renderView('back/arrete/_table-list-results.html.twig', [
            'searchArrete' => $searchArrete,
            'arretes' => $paginatedArretes,
            'pages' => (int) ceil($paginatedArretes->count() / $this->maxListPagination),
        ]);
        $titleListResult = $this->renderView('back/arrete/_title-list-results.html.twig', [
            'arretes' => $paginatedArretes,
        ]);
        $htmlTargetContents = [
            ['target' => '#table-list-results', 'content' => $tableListResult],
            ['target' => '#title-list-results', 'content' => $titleListResult],
        ];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
    }
}
