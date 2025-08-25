<?php

namespace App\Controller\Back;

use App\Entity\File;
use App\Entity\User;
use App\Form\SearchTerritoryFilesType;
use App\Repository\FileRepository;
use App\Service\ListFilters\SearchTerritoryFiles;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/documents-types')]
#[IsGranted('ROLE_ADMIN_TERRITORY')]
class AdminTerritoryFilesController extends AbstractController
{
    #[Route('/', name: 'back_admin_territory_files_index', methods: ['GET'])]
    public function index(
        Request $request,
        FileRepository $fileRepository,
        ParameterBagInterface $parameterBag,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $searchTerritoryFiles = new SearchTerritoryFiles($user);
        $form = $this->createForm(SearchTerritoryFilesType::class, $searchTerritoryFiles);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchTerritoryFiles = new SearchTerritoryFiles($user);
        }
        $maxListPagination = $parameterBag->get('standard_max_list_pagination');
        $paginatedFiles = $fileRepository->findFilteredPaginated($searchTerritoryFiles, $maxListPagination);

        $file = new File();
        if (!$this->isGranted('ROLE_ADMIN')) {
            // $zone->setTerritory($user->getFirstTerritory());
        }
        // $addForm = $this->createForm(SearchTerritoryFilesType::class, $file, ['action' => $this->generateUrl('back_zone_add')]);

        return $this->render('back/admin-territory-files/index.html.twig', [
            'form' => $form,
            // 'addForm' => $addForm,
            'searchTerritoryFiles' => $searchTerritoryFiles,
            'files' => $paginatedFiles,
            'pages' => (int) ceil($paginatedFiles->count() / $maxListPagination),
        ]);
    }
}
