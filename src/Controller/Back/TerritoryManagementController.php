<?php

namespace App\Controller\Back;

use App\Entity\File;
use App\Entity\User;
use App\Repository\ArreteRepository;
use App\Repository\FileRepository;
use App\Repository\TagRepository;
use App\Repository\ZoneRepository;
use App\Service\ListFilters\SearchTerritoryFiles;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
        ArreteRepository $arreteRepository,
        #[Autowire(param: 'standard_max_list_pagination')] int $maxListPagination,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $allTags = $tagRepository->findAllActive(null, $user);
        $allZones = $zoneRepository->findForUserAndTerritory($user, null);
        $countArretes = $arreteRepository->countForUser($user);

        $searchTerritoryFiles = new SearchTerritoryFiles($user);
        $territories = null;
        if (!$this->isGranted('ROLE_ADMIN')) {
            $territories = $user->getPartnersTerritories();
        }
        /** @var Paginator<File> $paginatedFiles */
        $paginatedFiles = $fileRepository->findFilteredPaginated($searchTerritoryFiles, $territories, $maxListPagination);

        return $this->render('back/territory-management/index.html.twig', [
            'countTags' => \count($allTags),
            'countDocuments' => $paginatedFiles->count(),
            'countZones' => \count($allZones),
            'countArretes' => $countArretes,
        ]);
    }
}
