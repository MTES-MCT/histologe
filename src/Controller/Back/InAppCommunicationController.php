<?php

namespace App\Controller\Back;

use App\Entity\InAppCommunication;
use App\Entity\User;
use App\Repository\InAppCommunicationRepository;
use App\Repository\InAppCommunicationUserRepository;
use App\Repository\Query\InAppCommunication\InAppCommunicationUserQuery;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/in-app-communication')]
class InAppCommunicationController extends AbstractController
{
    public function __construct(
        private readonly InAppCommunicationUserQuery $inAppCommunicationUserQuery,
    ) {
    }

    /**
     * @throws Exception
     */
    public function show(
        InAppCommunicationRepository $inAppCommunicationRepository,
        InAppCommunicationUserRepository $inAppCommunicationUserRepository,
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
                $this->inAppCommunicationUserQuery->markAsSeen($user, $inAppCommunication);
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
    ): JsonResponse {
        /** @var ?User $user */
        $user = $this->getUser();
        $inAppCommunications = $inAppCommunicationRepository->findForUser($user, $inAppCommunication->getId());
        if (!$inAppCommunications) {
            return new JsonResponse(['success' => true]);
        }

        $this->inAppCommunicationUserQuery->markAsClosed($user, $inAppCommunication);

        return new JsonResponse(['success' => true]);
    }
}
