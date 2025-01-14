<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\SignalementSearchQuery;
use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Service\Signalement\SearchFilter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/cartographie')]
class CartographieController extends AbstractController
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
    ) {
    }

    #[Route('/', name: 'back_cartographie')]
    public function index(
    ): Response {
        $title = 'Cartographie';

        return $this->render('back/cartographie/index.html.twig', [
            'title' => $title,
        ]);
    }

    #[Route('/signalements/', name: 'back_signalement_carto_json')]
    public function list(
        SessionInterface $session,
        SignalementRepository $signalementRepository,
        SearchFilter $searchFilter,
        Request $request,
        #[MapQueryString] ?SignalementSearchQuery $signalementSearchQuery = null,
    ): JsonResponse {
        $session->set('signalementSearchQuery', $signalementSearchQuery);
        $session->save();
        /** @var User $user */
        $user = $this->getUser();
        $filters = null !== $signalementSearchQuery
            ? $searchFilter->setRequest($signalementSearchQuery)->buildFilters($user)
            : [
                'isImported' => 'oui',
            ];
        $filters['authorized_codes_insee'] = $this->parameterBag->get('authorized_codes_insee');
        $signalements = $signalementRepository->findAllWithGeoData(
            $user,
            $filters,
            (int) $request->get('offset')
        );

        return $this->json(
            [
                'list' => $signalements,
                'filters' => $filters,
            ],
            Response::HTTP_OK,
            ['content-type' => 'application/json'],
            ['groups' => ['signalements:read']]
        );
    }
}
