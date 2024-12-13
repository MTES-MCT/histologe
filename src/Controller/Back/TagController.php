<?php

namespace App\Controller\Back;

use App\Entity\Tag;
use App\Entity\User;
use App\Form\AddTagType;
use App\Form\EditTagType;
use App\Form\SearchTagType;
use App\Manager\TagManager;
use App\Repository\TagRepository;
use App\Service\FormHelper;
use App\Service\SearchTag;
use App\Service\Signalement\SearchFilterOptionDataProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/bo/etiquettes')]
class TagController extends AbstractController
{
    public const MAX_LIST_PAGINATION = 50;

    #[Route('/', name: 'back_tags_index', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN_TERRITORY')]
    public function index(
        Request $request,
        TagRepository $tagRepository,
    ): Response {
        [$form, $searchTag, $paginatedTags] = $this->handleSearchTag($request, $tagRepository);

        $addForm = $this->createForm(AddTagType::class, null, ['action' => $this->generateUrl('back_tags_add')]);

        return $this->render('back/tags/index.html.twig', [
            'form' => $form,
            'addForm' => $addForm,
            'searchTag' => $searchTag,
            'tags' => $paginatedTags,
            'pages' => (int) ceil($paginatedTags->count() / self::MAX_LIST_PAGINATION),
        ]);
    }

    #[Route('/ajouter', name: 'back_tags_add', methods: 'POST')]
    public function addTag(
        Request $request,
        EntityManagerInterface $entityManager,
        TagAwareCacheInterface $cache,
    ): Response {
        $this->denyAccessUnlessGranted('TAG_CREATE');
        $tag = new Tag();
        if (!$this->isGranted('ROLE_ADMIN')) {
            /** @var User $user */
            $user = $this->getUser();
            $territory = $user->getFirstTerritory();
            $tag->setTerritory($territory);
        }
        $form = $this->createForm(AddTagType::class, $tag);

        $form->submit($request->getPayload()->all());
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tag);
            $entityManager->flush();
            $cache->invalidateTags([SearchFilterOptionDataProvider::CACHE_TAG, SearchFilterOptionDataProvider::CACHE_TAG.$tag->getTerritory()->getZip()]);
            $this->addFlash('success', 'L\'étiquette a bien été ajoutée.');
        }
        if ($form->isSubmitted() && !$form->isValid()) {
            $errors = FormHelper::getErrorsFromForm($form);
            $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => $errors];

            return $this->json($response, $response['code']);
        }

        return $this->json(['code' => Response::HTTP_OK]);
    }

    #[Route('/editer/{tag}', name: 'back_tags_edit', methods: 'POST')]
    public function editTag(
        Tag $tag,
        Request $request,
        EntityManagerInterface $entityManager,
        TagAwareCacheInterface $cache,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('TAG_EDIT', $tag);
        $form = $this->createForm(EditTagType::class, $tag);

        $form->submit($request->getPayload()->all());
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $cache->invalidateTags([SearchFilterOptionDataProvider::CACHE_TAG, SearchFilterOptionDataProvider::CACHE_TAG.$tag->getTerritory()->getZip()]);
            $this->addFlash('success', 'L\'étiquette a bien été éditée.');
        }
        if ($form->isSubmitted() && !$form->isValid()) {
            $errors = FormHelper::getErrorsFromForm($form);
            $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => $errors];

            return $this->json($response, $response['code']);
        }

        return $this->json(['code' => Response::HTTP_OK]);
    }

    #[Route('/supprimer', name: 'back_tags_delete', methods: 'POST')]
    public function deleteTag(
        Request $request,
        TagManager $tagManager,
        EntityManagerInterface $entityManager,
        TagAwareCacheInterface $cache,
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
            $cache->invalidateTags([SearchFilterOptionDataProvider::CACHE_TAG, SearchFilterOptionDataProvider::CACHE_TAG.$tag->getTerritory()->getZip()]);
            $this->addFlash('success', 'L\'étiquette a bien été supprimée.');

            return $this->redirectToRoute('back_tags_index', [], Response::HTTP_SEE_OTHER);
        }

        $this->addFlash('error', 'Une erreur est survenue lors de la suppression...');

        return $this->redirectToRoute('back_tags_index', [], Response::HTTP_SEE_OTHER);
    }

    private function handleSearchTag(Request $request, TagRepository $tagRepository): array
    {
        $searchTag = new SearchTag($this->getUser());
        $form = $this->createForm(SearchTagType::class, $searchTag);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchTag = new SearchTag($this->getUser());
        }
        $paginatedTags = $tagRepository->findFilteredPaginated($searchTag, self::MAX_LIST_PAGINATION);

        return [$form, $searchTag, $paginatedTags];
    }
}
