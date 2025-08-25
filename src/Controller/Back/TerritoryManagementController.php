<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Repository\TagRepository;
use App\Repository\ZoneRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/gerer-territoire')]
class TerritoryManagementController extends AbstractController
{
    #[Route('/', name: 'back_territory_management_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN_TERRITORY')]
    public function index(
        TagRepository $tagRepository,
        ZoneRepository $zoneRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $allTags = $tagRepository->findAllActive(null, $user);
        $allZones = $zoneRepository->findForUserAndTerritory($user, null);

        return $this->render('back/territory-management/index.html.twig', [
            'countTags' => \count($allTags),
            'countZones' => \count($allZones),
        ]);
    }
}
