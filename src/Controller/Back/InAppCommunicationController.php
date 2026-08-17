<?php

namespace App\Controller\Back;

use App\Entity\InAppCommunication;
use App\Entity\User;
use App\Repository\InAppCommunicationRepository;
use App\Repository\InAppCommunicationUserRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/in-app-communication')]
class InAppCommunicationController extends AbstractController
{
    /**
     * @throws Exception
     */
    public function show(
        InAppCommunicationRepository $inAppCommunicationRepository,
        InAppCommunicationUserRepository $inAppCommunicationUserRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var ?User $user */
        $user = $this->getUser();
        $inAppCommunications = $inAppCommunicationRepository->findForUser($user);
        $listInAppCommunications = [];
        // pour chaque communication a afficher à l'utilisateur :
        // - si c'est le premier affichage on l'enregistre
        // - si elle a déjà été clôturée on l'ignore
        foreach ($inAppCommunications as $inAppCommunication) {
            $inAppCommunicationUser = $inAppCommunicationUserRepository->findOneBy(['user' => $user, 'inAppCommunication' => $inAppCommunication]);
            if ($inAppCommunicationUser?->getClosedAt()) {
                continue;
            }

            if (!$inAppCommunicationUser) {
                $entityManager->getConnection()->executeStatement(
                    'INSERT IGNORE INTO in_app_communication_user (user_id, in_app_communication_id, seen_at) 
                     VALUES (:user_id, :communication_id, :seen_at)',
                    [
                        'user_id' => $user->getId(),
                        'communication_id' => $inAppCommunication->getId(),
                        'seen_at' => new \DateTimeImmutable()->format('Y-m-d H:i:s'),
                    ]
                );
            }

            $listInAppCommunications[] = $inAppCommunication;
        }

        return $this->render('back/in-app-communication/show.html.twig', [
            'inAppCommunications' => $listInAppCommunications,
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route('/close/{id}', name: 'back_in_app_communication_close', methods: ['POST'])]
    public function close(
        InAppCommunication $inAppCommunication,
        InAppCommunicationRepository $inAppCommunicationRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var ?User $user */
        $user = $this->getUser();
        $inAppCommunications = $inAppCommunicationRepository->findForUser($user, $inAppCommunication->getId());
        if (!$inAppCommunications) {
            return new JsonResponse(['success' => true]);
        }

        $now = new \DateTimeImmutable()->format('Y-m-d H:i:s');
        $entityManager->getConnection()->executeStatement(
            'INSERT INTO in_app_communication_user (user_id, in_app_communication_id, seen_at, closed_at) 
             VALUES (:user_id, :communication_id, :seen_at, :closed_at) 
             ON DUPLICATE KEY UPDATE closed_at = :closed_at',
            [
                'user_id' => $user->getId(),
                'communication_id' => $inAppCommunication->getId(),
                'seen_at' => $now,
                'closed_at' => $now,
            ]
        );

        return new JsonResponse(['success' => true]);
    }
}
