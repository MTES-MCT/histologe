<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Repository\FileRepository;
use App\Repository\TagRepository;
use App\Repository\ZoneRepository;
use App\Service\ListFilters\SearchTerritoryFiles;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/gerer-territoire')]
#[IsGranted('ROLE_ADMIN_TERRITORY')]
class TerritoryManagementController extends AbstractController
{
    #[Route('/', name: 'back_territory_management_index', methods: ['GET'])]
    public function index(
        TagRepository $tagRepository,
        ZoneRepository $zoneRepository,
        FileRepository $fileRepository,
        ParameterBagInterface $parameterBag,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $allTags = $tagRepository->findAllActive(null, $user);
        $allZones = $zoneRepository->findForUserAndTerritory($user, null);

        $maxListPagination = $parameterBag->get('standard_max_list_pagination');
        $searchTerritoryFiles = new SearchTerritoryFiles($user);
        $territory = null;
        if (!$this->isGranted('ROLE_ADMIN')) {
            $territory = $user->getFirstTerritory();
        }
        $paginatedFiles = $fileRepository->findFilteredPaginated($searchTerritoryFiles, $territory, $maxListPagination);

        return $this->render('back/territory-management/index.html.twig', [
            'countTags' => \count($allTags),
            'countDocuments' => $paginatedFiles->count(),
            'countZones' => \count($allZones),
        ]);
    }
}
