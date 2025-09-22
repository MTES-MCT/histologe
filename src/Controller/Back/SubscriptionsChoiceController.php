<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Repository\UserSignalementSubscriptionRepository;
use App\Service\DashboardTabPanel\Kpi\TabCountKpiCacheHelper;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/subscription-choice')]
class SubscriptionsChoiceController extends AbstractController
{
    #[Route('/choice', name: 'subscriptions_choice', methods: ['POST'])]
    public function choice(
        Request $request,
        UserSignalementSubscriptionRepository $userSignalementSubscriptionRepository,
        TabCountKpiCacheHelper $tabCountKpiCacheHelper,
        #[MapQueryString] TabQueryParameters $tabQueryParameter,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $jsonData = $request->toArray();
        $choice = $jsonData['subscriptions_choice'] ?? null;
        $token = $jsonData['_token'] ?? null;
        if (!$this->isCsrfTokenValid('subscriptions_choice', $token)) {
            $errorMsg = 'Le token CSRF est invalide, veuillez recharger la page.';
            $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => ['custom' => ['errors' => [$errorMsg]]]];
        } elseif (null === $choice) {
            $errorMsg = 'Veuillez faire un choix.';
            $response = ['code' => Response::HTTP_BAD_REQUEST,  'errors' => ['custom' => ['errors' => [$errorMsg]]]];
        }
        if (isset($response) && isset($errorMsg)) {
            return $this->json($response, $response['code']);
        }

        /** @var User $user */
        $user = $this->getUser();
        if ($choice) {
            $subs = $userSignalementSubscriptionRepository->findLegacyForUserInactiveOnSignalement($user);
            foreach ($subs as $sub) {
                $entityManager->remove($sub);
            }
            if (!$this->isGranted('ROLE_ADMIN_TERRITORY')) {
                $tabQueryParameter->mesDossiersMessagesUsagers = '1';
                $tabQueryParameter->mesDossiersAverifier = '1';
            }
            $isDeletedCacheOngletMessagesUsagers = $tabCountKpiCacheHelper->delete(
                TabCountKpiCacheHelper::DOSSIERS_MESSAGES_USAGERS,
                $user,
                $tabQueryParameter
            );
            $isDeletedCacheOngletAverifier = $tabCountKpiCacheHelper->delete(
                TabCountKpiCacheHelper::DOSSIERS_A_VERIFIER,
                $user,
                $tabQueryParameter
            );
        }
        $user->setHasDoneSubscriptionsChoice(true);
        $entityManager->flush();

        return $this->json(['code' => Response::HTTP_OK]);
    }
}
