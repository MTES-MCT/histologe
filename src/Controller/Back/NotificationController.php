<?php

namespace App\Controller\Back;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo')]
class NotificationController extends AbstractController
{
    #[Route('/notifications', name: 'back_notifications_list')]
    public function list(
        Request $request,
        NotificationRepository $notificationRepository,
    ): Response {
        $page = $request->get('page') ?? 1;
        $options = $this->getParameter('authorized_codes_insee');
        $notifications = $notificationRepository->getNotificationUser($this->getUser(), (int) $page, $options);

        return $this->render('back/notifications/index.html.twig', [
            'notifications' => $notifications,
            'page' => $page,
            'pages' => (int) ceil($notifications->count() / Notification::MAX_LIST_PAGINATION),
        ]);
    }

    #[Route('/notifications/lue', name: 'back_notifications_list_read')]
    public function read(
        Request $request,
        EntityManagerInterface $entityManager,
    ) {
        /** @var User $user */
        $user = $this->getUser();
        if ($request->get('selected_notifications')) {
            if ($this->isCsrfTokenValid('mark_as_read_'.$user->getId(), $request->get('csrf_token'))) {
                $q = $entityManager->createQuery('UPDATE '.Notification::class.' n SET n.isSeen = true WHERE n.user = :user AND n.id IN (:ids)');
                $q->execute(['user' => $user, 'ids' => explode(',', $request->get('selected_notifications'))]);
                $this->addFlash('success', 'Les notifications sélectionnées ont été marquées comme lues.');
            }
        } else {
            if ($this->isCsrfTokenValid('mark_as_read_'.$user->getId(), $request->get('mark_as_read'))) {
                $q = $entityManager->createQuery('UPDATE '.Notification::class.' n SET n.isSeen = true WHERE n.user = :user');
                $q->execute(['user' => $user]);
                $this->addFlash('success', 'Toutes les notifications ont été marquées comme lues.');
            }
        }

        return $this->redirectToRoute('back_notifications_list');
    }

    #[Route('/notifications/supprimer', name: 'back_notifications_list_delete')]
    public function delete(
        Request $request,
        EntityManagerInterface $entityManager,
    ) {
        /** @var User $user */
        $user = $this->getUser();
        if ($request->get('selected_notifications')) {
            if ($this->isCsrfTokenValid('delete_notifications_'.$user->getId(), $request->get('csrf_token'))) {
                $q = $entityManager->createQuery('DELETE FROM '.Notification::class.' n WHERE n.user = :user AND n.id IN (:ids)');
                $q->execute(['user' => $user, 'ids' => explode(',', $request->get('selected_notifications'))]);
                $this->addFlash('success', 'Les notifications sélectionnées ont été supprimées.');
            }
        } else {
            if ($this->isCsrfTokenValid(
                'delete_all_notifications_'.$user->getId(),
                $request->get('delete_all_notifications')
            )) {
                $q = $entityManager->createQuery('DELETE FROM '.Notification::class.' n WHERE n.user = :user');
                $q->execute(['user' => $user]);
                $this->addFlash('success', 'Toutes les notifications ont été supprimées.');
            }
        }

        return $this->redirectToRoute('back_notifications_list');
    }

    #[Route('/notifications/{id}/supprimer', name: 'back_notifications_delete_notification')]
    public function deleteNotification(
        NotificationRepository $notificationRepository,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $notification = $notificationRepository->find($request->get('id'));
        if (!$notification) {
            return $this->redirectToRoute('back_notifications_list');
        }
        /** @var User $user */
        $user = $this->getUser();
        if ($notification->getUser()->getId() === $user->getId() && $this->isCsrfTokenValid('back_delete_notification_'.$notification->getId(), $request->get('_token'))) {
            $em->remove($notification);
            $em->flush();
            $this->addFlash('success', 'Notification supprimée avec succès');
        } else {
            $this->addFlash('error', 'Erreur lors de la suppression de la notification.');
        }

        return $this->redirectToRoute('back_notifications_list');
    }
}
