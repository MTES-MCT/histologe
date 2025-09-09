<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\SignalementSearchQuery;
use App\Entity\User;
use App\Manager\SignalementManager;
use App\Service\Signalement\SearchFilter;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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
        SessionInterface $session,
        SignalementManager $signalementManager,
        SearchFilter $searchFilter,
        LoggerInterface $logger,
        #[MapQueryString] ?SignalementSearchQuery $signalementSearchQuery = null,
    ): JsonResponse {
        try {
            $session->set('signalementSearchQuery', $signalementSearchQuery);
            $session->save();

            // Vérification que la sauvegarde a bien fonctionné
            $savedQuery = $session->get('signalementSearchQuery');
            if ($savedQuery !== $signalementSearchQuery) {
                throw new \RuntimeException('Session data mismatch after save');
            }

            $logger->info('Session signalementSearchQuery saved successfully', [
                'session_id' => $session->getId(),
                'has_query' => null !== $signalementSearchQuery,
                'query_string' => $signalementSearchQuery ? $signalementSearchQuery->getQueryStringForUrl() : null,
            ]);
        } catch (\Exception $e) {
            $logger->error('Failed to save signalementSearchQuery to session', [
                'session_id' => $session->getId(),
                'error' => $e->getMessage(),
                'has_query' => null !== $signalementSearchQuery,
                'trace' => $e->getTraceAsString(),
            ]);

            // Retry une fois si la première tentative échoue
            try {
                sleep(1); // Attendre 1 seconde
                $session->set('signalementSearchQuery', $signalementSearchQuery);
                $session->save();

                $logger->info('Session signalementSearchQuery saved successfully (retry)', [
                    'session_id' => $session->getId(),
                ]);
            } catch (\Exception $retryException) {
                $logger->critical('Failed to save signalementSearchQuery to session after retry', [
                    'session_id' => $session->getId(),
                    'original_error' => $e->getMessage(),
                    'retry_error' => $retryException->getMessage(),
                ]);
            }
        }

        /** @var User $user */
        $user = $this->getUser();
        $filters = null !== $signalementSearchQuery
            ? $searchFilter->setRequest($signalementSearchQuery)->buildFilters($user)
            : [
                'maxItemsPerPage' => SignalementSearchQuery::MAX_LIST_PAGINATION,
                'orderBy' => 'DESC',
                'sortBy' => 'reference',
                'isImported' => 'oui',
            ];
        $signalements = $signalementManager->findSignalementAffectationList($user, $filters);

        return $this->json(
            $signalements,
            Response::HTTP_OK,
            ['content-type' => 'application/json'],
            ['groups' => ['signalements:read']]
        );
    }
}
