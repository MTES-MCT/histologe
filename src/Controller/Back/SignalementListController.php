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
        // SearchFilter $searchFilter,
        Request $request,
        UserSavedSearchRepository $savedSearchRepository,
        EntityManagerInterface $entityManager,
        // #[MapQueryString] ?SignalementSearchQuery $signalementSearchQuery = null,
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

        $name = $data['name'] ?? null;
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

        $search = new UserSavedSearch();
        $search->setUser($user);
        $search->setName($name); // on prend le nom envoyé par Vue
        $search->setParams($params);

        // dump($signalementSearchQuery);
        // $filters = null !== $signalementSearchQuery
        //     ? $searchFilter->setRequest($signalementSearchQuery)->buildFilters($user)
        //     : [];
        // dump($filters);

        // if (empty($filters)) {
        //     return $this->json([
        //         'status' => Response::HTTP_BAD_REQUEST,
        //         'message' => 'Aucun filtre actif, impossible d’enregistrer la recherche.',
        //     ], Response::HTTP_BAD_REQUEST);
        // }

        // $count = $savedSearchRepository->countForUser($user);

        // if ($count >= 5) {
        //     return $this->json([
        //         'status' => Response::HTTP_BAD_REQUEST,
        //         'message' => 'Vous avez atteint la limite de 5 recherches enregistrées.',
        //     ], Response::HTTP_BAD_REQUEST);
        // }

        // $generatedName = $this->generateReadableName($filters);
        // dump($generatedName);

        // $search = new UserSavedSearch();
        // $search->setUser($user);
        // $search->setName($generatedName);
        // $search->setParams($filters);

        $entityManager->persist($search);
        $entityManager->flush();

        return $this->json([
            'status' => Response::HTTP_OK,
            'message' => 'Votre recherche a bien été sauvegardée',
        ], Response::HTTP_OK);
    }
}
