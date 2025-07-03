<?php

namespace App\Controller\Back;

use App\Form\SearchInterconnexionType;
use App\Repository\JobEventRepository;
use App\Service\ListFilters\SearchInterconnexion;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/connexions-si')]
class InterconnexionController extends AbstractController
{
    #[Route('/', name: 'back_interconnexion_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        Request $request,
        JobEventRepository $jobEventRepository,
        ParameterBagInterface $parameterBag,
    ): Response {
        $searchInterconnexion = new SearchInterconnexion();
        $form = $this->createForm(SearchInterconnexionType::class, $searchInterconnexion);

        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchInterconnexion = new SearchInterconnexion();
        }
        // $maxListPagination = $parameterBag->get('standard_max_list_pagination');
        $maxListPagination = 5;

        $page = $searchInterconnexion->getPage() ?? 1;
        $limit = $maxListPagination;
        $offset = ($page - 1) * $limit;

        $connections = $jobEventRepository->findLastJobEventByTerritory(
            90,
            $searchInterconnexion,
            $limit,
            $offset
        );

        $total = $jobEventRepository->countLastJobEventByTerritory(
            90,
            $searchInterconnexion
        );
        $pages = (int) ceil($total / $limit);

        return $this->render('back/interconnexion/index.html.twig', [
            'form' => $form,
            'searchInterconnexion' => $searchInterconnexion,
            'connections' => $connections,
            'pages' => $pages,
            'totalConnexions' => $total,
        ]);
    }
}
