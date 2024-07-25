<?php

namespace App\Controller\Back;

use App\Repository\TagRepository;
use App\Repository\TerritoryRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/etiquettes')]
class TagController extends AbstractController
{
    public const MAX_LIST_PAGINATION = 50;

    #[Route('/', name: 'back_tags_index', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN_TERRITORY')]
    public function index(
        Request $request,
        TagRepository $tagRepository,
        TerritoryRepository $territoryRepository,
    ): Response {
        if ($request->isMethod(Request::METHOD_POST)) {
            $currentTerritory = $territoryRepository->find((int) $request->request->get('territory'));
            $search = $request->request->get('search');

            return $this->redirect($this->generateUrl('back_tags_index', [
                'page' => 1,
                'territory' => $currentTerritory?->getId(),
                'search' => $search,
            ]));
        }

        $page = $request->get('page') ?? 1;

        /** @var User $user */
        $user = $this->getUser();
        if ($this->isGranted('ROLE_ADMIN')) {
            $currentTerritory = $territoryRepository->find((int) $request->get('territory'));
        } else {
            $currentTerritory = $user->getTerritory();
        }
        $search = $request->get('search');

        $paginatedTagsResult = $tagRepository->findAllActivePaginated(
            territory: $currentTerritory,
            search: $search,
            page: (int) $page
        );
        $totalTags = $paginatedTagsResult['total'];
        $paginatedTags = $paginatedTagsResult['list'];

        return $this->render('back/tags/index.html.twig', [
            'currentTerritory' => $currentTerritory,
            'territories' => $territoryRepository->findAllList(),
            'tags' => $paginatedTags,
            'search' => $search,
            'total' => $totalTags,
            'page' => $page,
            'pages' => (int) ceil($totalTags / TagController::MAX_LIST_PAGINATION),
        ]);
    }
}
