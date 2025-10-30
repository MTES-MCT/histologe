<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Factory\SettingsFactory;
use App\Repository\TerritoryRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo')]
class SettingsController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/settings', name: 'back_settings')]
    public function index(
        SettingsFactory $settingsFactory,
        TerritoryRepository $territoryRepository,
        Security $security,
        #[MapQueryParameter] ?int $territoryId = null,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $authorizedTerritories = $user->getPartnersTerritories();

        $territory = null;
        if ($territoryId && ($security->isGranted('ROLE_ADMIN') || isset($authorizedTerritories[$territoryId]))) {
            $territory = $territoryRepository->find($territoryId);
        }

        return $this->json(
            $settingsFactory->createInstanceFrom($user, $territory),
            Response::HTTP_OK,
            ['content-type' => 'application/json'],
            ['groups' => ['settings:read']]
        );
    }
}
