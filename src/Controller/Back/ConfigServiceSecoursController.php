<?php

namespace App\Controller\Back;

use App\Entity\ServiceSecoursRoute;
use App\Form\SearchServiceSecoursRouteType;
use App\Form\ServiceSecoursRouteType;
use App\Repository\ServiceSecoursRouteRepository;
use App\Service\FormHelper;
use App\Service\ListFilters\SearchServiceSecoursRoute;
use App\Service\MessageHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/config-service-secours')]
#[IsGranted('ROLE_ADMIN')]
class ConfigServiceSecoursController extends AbstractController
{
    public function __construct(
        #[Autowire(param: 'standard_max_list_pagination')]
        private readonly int $maxListPagination,
        private readonly ServiceSecoursRouteRepository $serviceSecoursRouteRepository,
    ) {
    }

    /**
     * @return array{FormInterface, SearchServiceSecoursRoute, Paginator<ServiceSecoursRoute>}
     */
    private function handleSearch(Request $request, bool $fromSearchParams = false): array
    {
        $searchServiceSecoursRoute = new SearchServiceSecoursRoute();
        $form = $this->createForm(SearchServiceSecoursRouteType::class, $searchServiceSecoursRoute);
        FormHelper::handleFormSubmitFromRequestOrSearchParams($form, $request, $fromSearchParams);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchServiceSecoursRoute = new SearchServiceSecoursRoute();
        }
        /** @var Paginator<ServiceSecoursRoute> $paginatedServiceSecoursRoute */
        $paginatedServiceSecoursRoute = $this->serviceSecoursRouteRepository->findFilteredPaginated($searchServiceSecoursRoute, $this->maxListPagination);

        return [$form, $searchServiceSecoursRoute, $paginatedServiceSecoursRoute];
    }

    private function getHtmlTargetContentsForServiceSecoursRouteList(Request $request): array
    {
        [, $searchServiceSecoursRoute, $paginatedServiceSecoursRoute] = $this->handleSearch($request, true);

        return [
            [
                'target' => '#title-and-table-list-results',
                'content' => $this->renderView('back/config-service-secours-route/_title-and-table-list-results.html.twig', [
                    'searchServiceSecoursRoute' => $searchServiceSecoursRoute,
                    'serviceSecoursRoutes' => $paginatedServiceSecoursRoute,
                    'pages' => (int) ceil($paginatedServiceSecoursRoute->count() / $this->maxListPagination),
                ]),
            ],
        ];
    }

    #[Route('/', name: 'back_config_service_secours_route_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        [$form, $searchServiceSecoursRoute, $paginatedServiceSecoursRoute] = $this->handleSearch($request);

        return $this->render('back/config-service-secours-route/index.html.twig', [
            'form' => $form,
            'searchServiceSecoursRoute' => $searchServiceSecoursRoute,
            'serviceSecoursRoutes' => $paginatedServiceSecoursRoute,
            'pages' => (int) ceil($paginatedServiceSecoursRoute->count() / $this->maxListPagination),
        ]);
    }

    #[Route('/{id}/supprimer-route', name: 'back_config_service_secours_route_delete', methods: ['POST'])]
    public function deleteServiceSecoursRoute(
        Request $request,
        ServiceSecoursRoute $serviceSecoursRoute,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $flashMessages = [];
        if (!$this->isCsrfTokenValid('config_service_secours_route_delete', (string) $request->request->get('_token'))) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true]);
        }
        $entityManager->remove($serviceSecoursRoute);
        $entityManager->flush();
        $flashMessages[] = ['type' => 'success', 'title' => 'Règle archivée', 'message' => 'La règle a bien été archivée.'];

        $htmlTargetContents = $this->getHtmlTargetContentsForServiceSecoursRouteList($request);

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'closeModal' => true, 'htmlTargetContents' => $htmlTargetContents]);
    }

    #[Route('/{id}/editer', name: 'back_config_service_secours_route_edit', methods: ['GET', 'POST'])]
    public function editServiceSecoursRoute(
        Request $request,
        ServiceSecoursRoute $serviceSecoursRoute,
        EntityManagerInterface $entityManager,
    ): Response {
        $form = $this->createForm(ServiceSecoursRouteType::class, $serviceSecoursRoute);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', ['title' => 'Modifications enregistrées', 'message' => 'La règle a bien été modifiée.']);

            return $this->redirectToRoute('back_config_service_secours_route_index', []);
        }

        return $this->render('back/config-service-secours-route/edit.html.twig', [
            'serviceSecoursRoute' => $serviceSecoursRoute,
            'form' => $form,
            'create' => false,
        ]);
    }

    #[Route('/ajout', name: 'back_config_service_secours_route_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $serviceSecoursRoute = new ServiceSecoursRoute();
        $form = $this->createForm(ServiceSecoursRouteType::class, $serviceSecoursRoute);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($serviceSecoursRoute);
            $entityManager->flush();
            $this->addFlash('success', ['title' => 'Règle créée',
                'message' => 'La règle a bien été créée.',
            ]);

            return $this->redirectToRoute('back_config_service_secours_route_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back/config-service-secours-route/edit.html.twig', [
            'serviceSecoursRoute' => $serviceSecoursRoute,
            'form' => $form,
            'create' => true,
        ]);
    }
}
