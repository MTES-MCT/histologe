<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Manager\PopNotificationManager;
use App\Repository\PartnerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/pop-notification')]
class PopNotificationController extends AbstractController
{
    public function show(
        PartnerRepository $partnerRepository,
    ): Response {
        /** @var ?User $user */
        $user = $this->getUser();
        $popNotification = $user->getPopNotifications()->first();
        $addedPartnersIds = isset($popNotification->getParams()['addedPartners']) ? $popNotification->getParams()['addedPartners'] : [];
        $removedPartnersIds = isset($popNotification->getParams()['removedPartners']) ? $popNotification->getParams()['removedPartners'] : [];
        $addedPartners = $partnerRepository->findBy(['id' => $addedPartnersIds]);
        $removedPartners = $partnerRepository->findBy(['id' => $removedPartnersIds]);

        return $this->render('back/pop-notification/index.html.twig', [
            'user' => $user,
            'addedPartners' => $addedPartners,
            'removedPartners' => $removedPartners,
        ]);
    }

    #[Route('/delete', name: 'pop_notification_delete')]
    public function delete(
        PopNotificationManager $popNotificationManager,
    ): JsonResponse {
        /** @var ?User $user */
        $user = $this->getUser();
        $popNotification = $user->getPopNotifications()->count() ? $user->getPopNotifications()->first() : null;
        if ($popNotification) {
            //$popNotificationManager->remove($popNotification);
        }

        return new JsonResponse(['success' => true]);
    }
}
