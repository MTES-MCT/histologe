<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\SignalementSearchQuery;
use App\Entity\User;
use App\Entity\UserSavedSearch;
use App\Factory\SignalementSearchQueryFactory;
use App\Manager\SignalementManager;
use App\Repository\UserSavedSearchRepository;
use App\Service\Signalement\SearchFilter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo')]
class SignalementListController extends AbstractController
{
    #[Route('/signalements/', name: 'back_signalements_index')]
    public function show(): Response
    {
        return $this->render('back/signalement/list/index.html.twig');
    }

    #[Route('/list/signalements/', name: 'back_signalements_list_json')]
    public function list(
        SignalementManager $signalementManager,
        SearchFilter $searchFilter,
        #[MapQueryString] ?SignalementSearchQuery $signalementSearchQuery = null,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $filters = null !== $signalementSearchQuery
            ? $searchFilter->setRequest($signalementSearchQuery)->buildFilters($user)
            : [
                'maxItemsPerPage' => SignalementSearchQuery::MAX_LIST_PAGINATION,
                'orderBy' => 'DESC',
                'sortBy' => 'reference',
            ];
        $signalements = $signalementManager->findSignalementAffectationList($user, $filters);

        $response = $this->json(
            $signalements,
            Response::HTTP_OK,
            ['content-type' => 'application/json'],
            ['groups' => ['signalements:read']]
        );

        // Remove '?' at the start of the string
        $parsableQueryString = null !== $signalementSearchQuery
            ? substr($signalementSearchQuery->getQueryStringForUrl(), 1)
            : '';
        $cookie = Cookie::create(SignalementSearchQueryFactory::COOKIE_NAME)
            ->withValue($parsableQueryString)
            ->withExpires(strtotime('+1 hour'));

        $response->headers->setCookie($cookie);

        return $response;
    }

    #[Route('/list/signalements/search/save', name: 'back_signalements_list_save_search')]
    public function saveSearch(
        Request $request,
        UserSavedSearchRepository $savedSearchRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $csrfToken = $data['_token'] ?? null;
        if (!$this->isCsrfTokenValid('save_search', $csrfToken)) {
            return $this->json([
                'status' => Response::HTTP_FORBIDDEN,
                'message' => 'Token CSRF invalide.',
            ], Response::HTTP_FORBIDDEN);
        }

        $name = trim($data['name']);
        if (empty($name)) {
            return $this->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'Aucun nom, impossible d’enregistrer la recherche.',
            ], Response::HTTP_BAD_REQUEST);
        }

        if (mb_strlen($name) > 50) {
            return $this->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'Le nom ne peut pas dépasser 50 caractères.',
            ], Response::HTTP_BAD_REQUEST);
        }
        $params = $data['params'] ?? null;
        if (empty($params)) {
            return $this->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'Aucun filtre actif, impossible d’enregistrer la recherche.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $count = $savedSearchRepository->countForUser($user);
        if ($count >= 5) {
            return $this->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'Vous avez atteint la limite de 5 recherches enregistrées.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $normalizedNew = $this->normalizeParams($params);
        $existingSearches = $savedSearchRepository->findBy(['user' => $user]);
        foreach ($existingSearches as $existing) {
            $existingParams = $existing->getParams();
            if (!is_array($existingParams)) {
                continue;
            }
            $normalizedExisting = $this->normalizeParams($existingParams);
            if ($normalizedExisting === $normalizedNew) {
                return $this->json([
                    'status' => Response::HTTP_BAD_REQUEST,
                    'message' => 'Vous avez déjà une recherche avec les mêmes filtres.',
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        $search = new UserSavedSearch();
        $search->setUser($user);
        $search->setName($name);
        $search->setParams($params);

        $entityManager->persist($search);
        $entityManager->flush();

        return $this->json([
            'status' => Response::HTTP_OK,
            'message' => 'Votre recherche a bien été sauvegardée',
            'data' => [
                'savedSearch' => [
                    'id' => $search->getId(),
                    'name' => $search->getName(),
                    'params' => $search->getParams(),
                ],
            ],
        ], Response::HTTP_OK);
    }

    #[Route('/list/signalements/search/delete/{id}', name: 'back_signalements_list_delete_search', methods: ['POST'])]
    public function deleteSavedSearch(
        int $id,
        Request $request,
        UserSavedSearchRepository $savedSearchRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $csrfToken = $data['_token'] ?? null;
        if (!$this->isCsrfTokenValid('delete_search', $csrfToken)) {
            return $this->json([
                'status' => Response::HTTP_FORBIDDEN,
                'message' => 'Token CSRF invalide.',
            ], Response::HTTP_FORBIDDEN);
        }

        $savedSearch = $savedSearchRepository->findOneBy(['id' => $id, 'user' => $user]);
        if (!$savedSearch) {
            return $this->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'Recherche introuvable',
            ], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($savedSearch);
        $entityManager->flush();

        return $this->json([
            'status' => Response::HTTP_OK,
            'message' => 'Recherche supprimée',
        ]);
    }

    #[Route('/list/signalements/search/edit/{id}', name: 'back_signalements_list_edit_search', methods: ['POST'])]
    public function editSavedSearch(
        int $id,
        Request $request,
        UserSavedSearchRepository $savedSearchRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $csrfToken = $data['_token'] ?? null;
        if (!$this->isCsrfTokenValid('edit_search', $csrfToken)) {
            return $this->json([
                'status' => Response::HTTP_FORBIDDEN,
                'message' => 'Token CSRF invalide.',
            ], Response::HTTP_FORBIDDEN);
        }

        $name = trim($data['name']);
        if (empty($name)) {
            return $this->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'Aucun nom, impossible de modifier la recherche.',
            ], Response::HTTP_BAD_REQUEST);
        }
        if (mb_strlen($name) > 50) {
            return $this->json([
                'status' => Response::HTTP_BAD_REQUEST,
                'message' => 'Le nom ne peut pas dépasser 60 caractères.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $savedSearch = $savedSearchRepository->findOneBy(['id' => $id, 'user' => $user]);
        if (!$savedSearch) {
            return $this->json([
                'status' => Response::HTTP_NOT_FOUND,
                'message' => 'Recherche introuvable',
            ], Response::HTTP_NOT_FOUND);
        }

        $savedSearch->setName($name);
        $entityManager->flush();

        return $this->json([
            'status' => Response::HTTP_OK,
            'message' => 'Recherche éditée',
        ]);
    }

    /**
     * @param array<mixed> $params
     *
     * @return array<mixed>
     */
    private function normalizeParams(array $params): array
    {
        ksort($params);
        foreach ($params as &$value) {
            if (is_array($value)) {
                sort($value);
            }
        }

        return $params;
    }
}
