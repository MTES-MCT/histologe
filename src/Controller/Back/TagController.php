<?php

namespace App\Controller\Back;

use App\Entity\Tag;
use App\Manager\TagManager;
use App\Repository\TagRepository;
use App\Repository\TerritoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

        $paginatedTags = $tagRepository->findAllActivePaginated(
            territory: $currentTerritory,
            search: $search,
            page: (int) $page
        );
        $totalTags = \count($paginatedTags);

        return $this->render('back/tags/index.html.twig', [
            'currentTerritory' => $currentTerritory,
            'territories' => $territoryRepository->findAllList(),
            'tags' => $paginatedTags,
            'search' => $search,
            'total' => $totalTags,
            'page' => $page,
            'pages' => (int) ceil($totalTags / self::MAX_LIST_PAGINATION),
        ]);
    }

    #[Route('/ajouter', name: 'back_tags_add', methods: 'POST')]
    public function addTag(
        Request $request,
        TagManager $tagManager,
        EntityManagerInterface $entityManager,
        TagRepository $tagRepository,
        TerritoryRepository $territoryRepository,
    ): Response {
        $this->denyAccessUnlessGranted('TAG_CREATE');

        if (
            $this->isCsrfTokenValid('tag_add', $request->request->get('_token'))
        ) {
            if ($this->isGranted('ROLE_ADMIN')) {
                $territory = $territoryRepository->find((int) $request->request->get('tag_territory'));
            } else {
                /** @var User $user */
                $user = $this->getUser();
                $territory = $user->getTerritory() ?? $user->getPartner()?->getTerritory();
            }

            $tagLabel = $request->request->get('tag_label');
            if (empty($tagLabel) || empty($territory)) {
                $this->addFlash('error', 'Merci de saisir un nom pour l\'étiquette.');

                return $this->redirectToRoute('back_tags_index', [], Response::HTTP_SEE_OTHER);
            }

            $alreadyExistingTags = $tagRepository->findBy([
                'isArchive' => 0,
                'label' => $tagLabel,
                'territory' => $territory,
            ]);
            if (!empty($alreadyExistingTags)) {
                $this->addFlash('error', 'Une étiquette avec le même nom existe déjà sur ce territoire...');

                return $this->redirectToRoute('back_tags_index', [], Response::HTTP_SEE_OTHER);
            }

            $tag = new Tag();
            $tag->setTerritory($territory);
            $tag->setLabel($tagLabel);
            $entityManager->persist($tag);
            $entityManager->flush();

            $this->addFlash('success', 'L\'étiquette a bien été ajoutée.');

            return $this->redirectToRoute('back_tags_index', [], Response::HTTP_SEE_OTHER);
        }

        $this->addFlash('error', 'Une erreur est survenue lors de l\'ajout...');

        return $this->redirectToRoute('back_tags_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/editer', name: 'back_tags_edit', methods: 'POST')]
    public function editTag(
        Request $request,
        TagManager $tagManager,
        EntityManagerInterface $entityManager,
        TagRepository $tagRepository,
    ): Response {
        $tagId = $request->request->get('tag_id');
        /** @var Tag $tag */
        $tag = $tagManager->find($tagId);
        $this->denyAccessUnlessGranted('TAG_EDIT', $tag);

        if (
            $tag
            && $this->isCsrfTokenValid('tag_edit', $request->request->get('_token'))
        ) {
            $tagLabel = $request->request->get('tag_label');
            if (empty($tagLabel)) {
                $this->addFlash('error', 'Merci de saisir un nom pour l\'étiquette.');

                return $this->redirectToRoute('back_tags_index', [], Response::HTTP_SEE_OTHER);
            }

            $alreadyExistingTags = $tagRepository->findBy([
                'isArchive' => 0,
                'label' => $tagLabel,
                'territory' => $tag->getTerritory(),
            ]);
            if (!empty($alreadyExistingTags)) {
                $this->addFlash('error', 'Une étiquette avec le même nom existe déjà sur ce territoire...');

                return $this->redirectToRoute('back_tags_index', [], Response::HTTP_SEE_OTHER);
            }

            $tag->setLabel($tagLabel);
            $entityManager->flush();

            $this->addFlash('success', 'L\'étiquette a bien été éditée.');

            return $this->redirectToRoute('back_tags_index', [], Response::HTTP_SEE_OTHER);
        }

        $this->addFlash('error', 'Une erreur est survenue lors de l\'édition...');

        return $this->redirectToRoute('back_tags_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/supprimer', name: 'back_tags_delete', methods: 'POST')]
    public function deleteTag(
        Request $request,
        TagManager $tagManager,
        EntityManagerInterface $entityManager,
    ): Response {
        $tagId = $request->request->get('tag_id');
        /** @var Tag $tag */
        $tag = $tagManager->find($tagId);
        $this->denyAccessUnlessGranted('TAG_DELETE', $tag);

        if (
            $tag
            && $this->isCsrfTokenValid('tag_delete', $request->request->get('_token'))
        ) {
            $tag->setIsArchive(true);
            $entityManager->flush();
            $this->addFlash('success', 'L\'étiquette a bien été supprimée.');

            return $this->redirectToRoute('back_tags_index', [], Response::HTTP_SEE_OTHER);
        }

        $this->addFlash('error', 'Une erreur est survenue lors de la suppression...');

        return $this->redirectToRoute('back_tags_index', [], Response::HTTP_SEE_OTHER);
    }
}
