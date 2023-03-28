<?php

namespace App\Controller\Back;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo')]
class BackNotificationController extends AbstractController
{
    #[Route('/notifications', name: 'back_notifications_list')]
    public function newsActivitiesSinceLastLogin(
        Request $request,
        NotificationRepository $notificationRepository,
    ): Response {
        $title = 'Administration - Nouveauté(s)';

        $page = $request->get('page') ?? 1;
        $options = $this->getParameter('authorized_codes_insee');
        $notifications = $notificationRepository->getFromUser($this->getUser(), (int) $page, $options);

        return $this->render('back/notifications/index.html.twig', [
            'title' => $title,
            'notifications' => $notifications,
            'page' => $page,
            'pages' => (int) ceil($notifications->count() / Notification::MAX_LIST_PAGINATION),
        ]);
    }

    #[Route('/notifications/lue', name: 'back_notifications_list_read')]
    public function read(
        Request $request,
        EntityManagerInterface $entityManager
    ) {
        if ($this->isCsrfTokenValid('mark_as_read_'.$this->getUser()->getId(), $request->get('mark_as_read'))) {
            $this->markAllAsRead($entityManager);
            $this->addFlash('success', 'Toutes les notifications marquées comme lues.');
        }

        return $this->redirectToRoute('back_notifications_list');
    }

    #[Route('/notifications/supprimer', name: 'back_notifications_list_delete')]
    public function delete(
        Request $request,
        EntityManagerInterface $entityManager
    ) {
        if ($this->isCsrfTokenValid('delete_all_notifications_'.$this->getUser()->getId(),
            $request->get('delete_all_notifications'))) {
            $this->deleteAllNotifications($entityManager);
            $this->addFlash('success', 'Toutes les notifications ont été supprimées.');
        }

        return $this->redirectToRoute('back_notifications_list');
    }

    private function markAllAsRead($em)
    {
        $notifications = $this->getUser()->getNotifications();
        $notifications->filter(function (Notification $notification) use ($em) {
            $this->denyAccessUnlessGranted('NOTIF_MARK_AS_READ', $notification);
            $notification->setIsSeen(true);
            $em->persist($notification);
        });
        $em->flush();
    }

    private function deleteAllNotifications($em)
    {
        $notifications = $this->getUser()->getNotifications();
        $notifications->filter(function (Notification $notification) use ($em) {
            $this->denyAccessUnlessGranted('NOTIF_DELETE', $notification);
            $em->remove($notification);
        });
        $em->flush();
    }

    #[Route('/notifications/{id}/supprimer', name: 'back_notifications_delete_notification')]
    public function deleteNotification(Notification $notification, EntityManagerInterface $em, Request $request): Response
    {
        $this->denyAccessUnlessGranted('NOTIF_DELETE', $notification);
        if ($this->isCsrfTokenValid('back_delete_notification_'.$notification->getId(), $request->get('_token'))) {
            $em->remove($notification);
            $em->flush();
            $this->addFlash('success', 'Notification supprimée avec succès');
        } else {
            $this->addFlash('error', 'Erreur lors de la suppression de la notification.');
        }

        return $this->redirectToRoute('back_notifications_list');
    }
}
