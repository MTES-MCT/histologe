<?php

namespace App\Controller\Back;

use App\Entity\Territory;
use App\Entity\User;
use App\Repository\BailleurRepository;
use App\Security\Voter\TerritoryVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/territoires')]
class BackTerritoryController extends AbstractController
{
    #[Route('/{territory}/bailleurs', name: 'back_territory_bailleurs', methods: ['GET'])]
    public function bailleursByTerritory(
        Territory $territory,
        BailleurRepository $bailleurRepository,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(TerritoryVoter::TERRITORY_GET_BAILLEURS_LIST, $territory);
        /** @var User $user */
        $user = $this->getUser();
        $bailleurs = $bailleurRepository->findBailleursByTerritory($user, $territory);

        return $this->json($bailleurs);
    }
}
