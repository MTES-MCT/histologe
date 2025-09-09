<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\SignalementSearchQuery;
use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Repository\ZoneRepository;
use App\Service\Signalement\SearchFilter;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/cartographie')]
class CartographieController extends AbstractController
{
    #[Route('/', name: 'back_cartographie')]
    public function index(
    ): Response {
        return $this->render('back/cartographie/index.html.twig');
    }

    #[Route('/signalements/', name: 'back_signalement_carto_json')]
    public function list(
        SessionInterface $session,
        SignalementRepository $signalementRepository,
        ZoneRepository $zoneRepository,
        SearchFilter $searchFilter,
        LoggerInterface $logger,
        Request $request,
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

            $logger->info('Session signalementSearchQuery saved successfully (cartographie)', [
                'session_id' => $session->getId(),
                'has_query' => null !== $signalementSearchQuery,
                'query_string' => $signalementSearchQuery ? $signalementSearchQuery->getQueryStringForUrl() : null,
            ]);
        } catch (\Exception $e) {
            $logger->error('Failed to save signalementSearchQuery to session (cartographie)', [
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

                $logger->info('Session signalementSearchQuery saved successfully (cartographie retry)', [
                    'session_id' => $session->getId(),
                ]);
            } catch (\Exception $retryException) {
                $logger->critical('Failed to save signalementSearchQuery to session after retry (cartographie)', [
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
                'isImported' => 'oui',
            ];
        $signalements = $signalementRepository->findAllWithGeoData(
            $user,
            $filters,
            (int) $request->get('offset')
        );
        $zoneAreas = [];
        if (!empty($filters['zones'])) {
            $criteria = ['id' => $filters['zones']];
            if (!$this->isGranted('ROLE_ADMIN')) {
                $criteria['territory'] = $user->getPartnersTerritories();
            }
            $zones = $zoneRepository->findBy($criteria);
            foreach ($zones as $zone) {
                $zoneAreas[] = $zone->getArea();
            }
        } elseif (!empty($filters['isZonesDisplayed'])) {
            $criteria = [];
            if (!empty($filters['territories'])) {
                $criteria['territory'] = $filters['territories'];
            } elseif (!$this->isGranted('ROLE_ADMIN')) {
                $criteria['territory'] = $user->getPartnersTerritories();
            }
            $zones = $zoneRepository->findBy($criteria);
            foreach ($zones as $zone) {
                $zoneAreas[] = $zone->getArea();
            }
        }

        return $this->json(
            [
                'list' => $signalements,
                'filters' => $filters,
                'zoneAreas' => $zoneAreas,
            ],
            Response::HTTP_OK,
            ['content-type' => 'application/json'],
            ['groups' => ['signalements:read']]
        );
    }
}
