<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Repository\UserSignalementSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/subscription-choice')]
class SubscriptionsChoiceController extends AbstractController
{
    #[Route('/choice', name: 'subscriptions_choice', methods: ['POST'])]
    public function choice(
        Request $request,
        UserSignalementSubscriptionRepository $userSignalementSubscriptionRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        $jsonData = $request->toArray();
        $choice = $jsonData['subscriptions_choice'] ?? null;
        $token = $jsonData['_token'] ?? null;
        if (!$this->isCsrfTokenValid('subscriptions_choice', $token)) {
            $errorMsg = 'Le token CSRF est invalide, veuillez recharger la page.';
            $response = ['code' => Response::HTTP_BAD_REQUEST, 'errors' => ['custom' => ['errors' => [$errorMsg]]]];

            return $this->json($response, $response['code']);
        }
        if (null === $choice) {
            $errorMsg = 'Veuillez faire un choix.';
            $response = ['code' => Response::HTTP_BAD_REQUEST,  'errors' => ['custom' => ['errors' => [$errorMsg]]]];

            return $this->json($response, $response['code']);
        }

        /** @var User $user */
        $user = $this->getUser();
        if ($choice) {
            $subs = $userSignalementSubscriptionRepository->findLegacyForUserInactiveOnSignalement($user);
            foreach ($subs as $sub) {
                $entityManager->remove($sub);
            }
        }
        $user->setHasDoneSubscriptionsChoice(true);
        $entityManager->flush();

        return $this->json(['code' => Response::HTTP_OK]);
    }
}
