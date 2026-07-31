<?php

namespace App\Controller\Back;

use App\Entity\InAppCommunication;
use App\Entity\InAppCommunicationUser;
use App\Entity\User;
use App\Repository\InAppCommunicationRepository;
use App\Repository\InAppCommunicationUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/in-app-communication')]
class InAppCommunicationController extends AbstractController
{
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
            if (!$inAppCommunicationUser) {
                $inAppCommunicationUser = new InAppCommunicationUser();
                $inAppCommunicationUser->setUser($user);
                $inAppCommunicationUser->setInAppCommunication($inAppCommunication);
                $inAppCommunicationUser->setSeenAt(new \DateTimeImmutable());
                $entityManager->persist($inAppCommunicationUser);
            } elseif ($inAppCommunicationUser->getClosedAt()) {
                continue;
            }
            $listInAppCommunications[] = $inAppCommunication;
        }

        $entityManager->flush();

        return $this->render('back/in-app-communication/show.html.twig', [
            'inAppCommunications' => $listInAppCommunications,
        ]);
    }

    #[Route('/close/{id}', name: 'back_in_app_communication_close', methods: ['POST'])]
    public function close(
        InAppCommunication $inAppCommunication,
        InAppCommunicationRepository $inAppCommunicationRepository,
        InAppCommunicationUserRepository $inAppCommunicationUserRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        /** @var ?User $user */
        $user = $this->getUser();
        $inAppCommunications = $inAppCommunicationRepository->findForUser($user, $inAppCommunication->getId());
        if (!$inAppCommunications) {
            return new JsonResponse(['success' => true]);
        }
        $inAppCommunicationUser = $inAppCommunicationUserRepository->findOneBy(['user' => $user, 'inAppCommunication' => $inAppCommunication]);
        if (!$inAppCommunicationUser) {
            $inAppCommunicationUser = new InAppCommunicationUser();
            $inAppCommunicationUser->setUser($user);
            $inAppCommunicationUser->setInAppCommunication($inAppCommunication);
            $inAppCommunicationUser->setSeenAt(new \DateTimeImmutable());
            $entityManager->persist($inAppCommunicationUser);
        }
        $inAppCommunicationUser->setClosedAt(new \DateTimeImmutable());
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
