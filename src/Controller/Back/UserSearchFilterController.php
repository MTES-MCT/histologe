<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\SignalementSearchQuery;
use App\Dto\Request\UserSearchFilterRequest;
use App\Entity\User;
use App\Entity\UserSearchFilter;
use App\Repository\UserSearchFilterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/bo')]
class UserSearchFilterController extends AbstractController
{
    #[Route('/user/search-filters/save', name: 'back_user_search_filters_save')]
    public function saveSearchFilter(
        #[MapRequestPayload(validationGroups: ['false'])]
        UserSearchFilterRequest $userSearchFilterRequest,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('save_search', $userSearchFilterRequest->_token)) {
            return $this->jsonForbidden();
        }

        $errors = $validator->validate($userSearchFilterRequest);
        if (count($errors) > 0) {
            return $this->jsonValidation($errors);
        }

        if (empty($userSearchFilterRequest->params)) {
            return $this->jsonBadRequest("Aucun filtre n'a été transmis.");
        }

        $searchQuery = SignalementSearchQuery::fromParams($userSearchFilterRequest->params);
        $errors = $validator->validate($searchQuery);
        if (count($errors) > 0) {
            return $this->jsonValidation($errors);
        }

        $search = new UserSearchFilter();
        $search->setUser($user);
        $search->setName($userSearchFilterRequest->name);
        $search->setParams($userSearchFilterRequest->params);

        $errors = $validator->validate($search);
        if (count($errors) > 0) {
            return $this->jsonValidation($errors);
        }

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

    #[Route('/user/search-filters/delete/{id}', name: 'back_user_search_filters_delete', methods: ['POST'])]
    public function deleteSearchFilter(
        int $id,
        Request $request,
        UserSearchFilterRepository $userSearchFilterRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $csrfToken = $data['_token'] ?? null;
        if (!$this->isCsrfTokenValid('delete_search', $csrfToken)) {
            return $this->jsonForbidden();
        }

        $userSearchFilter = $userSearchFilterRepository->findOneBy(['id' => $id, 'user' => $user]);
        if (!$userSearchFilter) {
            return $this->jsonNotFound('Recherche introuvable.');
        }

        $entityManager->remove($userSearchFilter);
        $entityManager->flush();

        return $this->json([
            'status' => Response::HTTP_OK,
            'message' => 'Recherche supprimée',
        ], Response::HTTP_OK);
    }

    #[Route('/user/search-filters/edit/{id}', name: 'back_user_search_filters_edit', methods: ['POST'])]
    public function editSearchFilter(
        int $id,
        #[MapRequestPayload(validationGroups: ['false'])]
        UserSearchFilterRequest $userSearchFilterRequest,
        ValidatorInterface $validator,
        UserSearchFilterRepository $userSearchFilterRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('edit_search', $userSearchFilterRequest->_token)) {
            return $this->jsonForbidden();
        }

        $errors = $validator->validate($userSearchFilterRequest);
        if (count($errors) > 0) {
            return $this->jsonValidation($errors);
        }
        $name = trim($userSearchFilterRequest->name);

        $userSearchFilter = $userSearchFilterRepository->findOneBy(['id' => $id, 'user' => $user]);
        if (!$userSearchFilter) {
            return $this->jsonNotFound('Recherche introuvable.');
        }

        $userSearchFilter->setName($name);
        $errors = $validator->validate($userSearchFilter);
        if (count($errors) > 0) {
            return $this->jsonValidation($errors);
        }
        $entityManager->flush();

        return $this->json([
            'status' => Response::HTTP_OK,
            'message' => 'Recherche éditée',
        ], Response::HTTP_OK);
    }

    private function jsonValidation(iterable $errors): JsonResponse
    {
        $messages = [];
        foreach ($errors as $e) {
            $messages[] = $e->getMessage();
        }

        return new JsonResponse([
            'status' => Response::HTTP_BAD_REQUEST,
            'message' => implode("\n", $messages),
        ], Response::HTTP_BAD_REQUEST);
    }

    private function jsonForbidden(string $msg = 'Le jeton CSRF est invalide. Veuillez actualiser la page et réessayer.'): JsonResponse
    {
        return new JsonResponse(['status' => Response::HTTP_FORBIDDEN, 'message' => $msg], Response::HTTP_FORBIDDEN);
    }

    private function jsonNotFound(string $msg): JsonResponse
    {
        return new JsonResponse(['status' => Response::HTTP_NOT_FOUND, 'message' => $msg], Response::HTTP_NOT_FOUND);
    }

    private function jsonBadRequest(string $msg): JsonResponse
    {
        return new JsonResponse(['status' => Response::HTTP_BAD_REQUEST, 'message' => $msg], Response::HTTP_BAD_REQUEST);
    }
}
