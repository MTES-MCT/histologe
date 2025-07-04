<?php

namespace App\Controller\Back;

use App\Entity\Notification;
use App\Entity\User;
use App\Form\SearchNotificationType;
use App\Repository\NotificationRepository;
use App\Service\DashboardWidget\WidgetDataManagerCache;
use App\Service\ListFilters\SearchNotification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo')]
class NotificationController extends AbstractController
{
    #[Route('/notifications', name: 'back_notifications_list')]
    public function list(
        Request $request,
        NotificationRepository $notificationRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $searchNotification = new SearchNotification($user);
        $form = $this->createForm(SearchNotificationType::class, $searchNotification);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchNotification = new SearchNotification($user);
        }
        $maxListPagination = Notification::MAX_LIST_PAGINATION;
        $paginatedNotifications = $notificationRepository->findFilteredPaginated($searchNotification, $maxListPagination);

        return $this->render('back/notifications/index.html.twig', [
            'form' => $form,
            'searchNotification' => $searchNotification,
            'notifications' => $paginatedNotifications,
            'pages' => (int) ceil($paginatedNotifications->count() / $maxListPagination),
        ]);
    }

    #[Route('/notifications/lue', name: 'back_notifications_list_read')]
    public function read(
        Request $request,
        NotificationRepository $notificationRepository,
        WidgetDataManagerCache $widgetDataManagerCache,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if ($request->get('selected_notifications')) {
            if ($this->isCsrfTokenValid('mark_as_read_'.$user->getId(), $request->get('csrf_token'))) {
                $notificationRepository->markUserNotificationsAsSeen($user, explode(',', $request->get('selected_notifications')));
                $widgetDataManagerCache->invalidateCacheForUser($user->getPartnersTerritories());
                $this->addFlash('success', 'Les notifications sélectionnées ont été marquées comme lues.');
            }
        } else {
            if ($this->isCsrfTokenValid('mark_as_read_'.$user->getId(), $request->get('mark_as_read'))) {
                $notificationRepository->markUserNotificationsAsSeen($user);
                $widgetDataManagerCache->invalidateCacheForUser($user->getPartnersTerritories());
                $this->addFlash('success', 'Toutes les notifications ont été marquées comme lues.');
            }
        }

        return $this->redirectToRoute('back_notifications_list');
    }

    #[Route('/notifications/supprimer', name: 'back_notifications_list_delete')]
    public function delete(
        Request $request,
        NotificationRepository $notificationRepository,
        WidgetDataManagerCache $widgetDataManagerCache,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if ($request->get('selected_notifications')) {
            if ($this->isCsrfTokenValid('delete_notifications_'.$user->getId(), $request->get('csrf_token'))) {
                $notificationRepository->deleteUserNotifications($user, explode(',', $request->get('selected_notifications')));
                $widgetDataManagerCache->invalidateCacheForUser($user->getPartnersTerritories());
                $this->addFlash('success', 'Les notifications sélectionnées ont été supprimées.');
            }
        } else {
            if ($this->isCsrfTokenValid(
                'delete_all_notifications_'.$user->getId(),
                $request->get('delete_all_notifications')
            )) {
                $notificationRepository->deleteUserNotifications($user);
                $widgetDataManagerCache->invalidateCacheForUser($user->getPartnersTerritories());
                $this->addFlash('success', 'Toutes les notifications ont été supprimées.');
            }
        }

        return $this->redirectToRoute('back_notifications_list');
    }

    #[Route('/notifications/{id}/supprimer', name: 'back_notifications_delete_notification')]
    public function deleteNotification(
        NotificationRepository $notificationRepository,
        EntityManagerInterface $em,
        Request $request,
        WidgetDataManagerCache $widgetDataManagerCache,
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
            $widgetDataManagerCache->invalidateCacheForUser($user->getPartnersTerritories());
            $this->addFlash('success', 'Notification supprimée avec succès');
        } else {
            $this->addFlash('error', 'Erreur lors de la suppression de la notification.');
        }

        return $this->redirectToRoute('back_notifications_list');
    }
}
