<?php

namespace App\Controller\Back;

use App\Entity\Notification;
use App\Entity\User;
use App\Form\SearchNotificationType;
use App\Repository\NotificationRepository;
use App\Service\ListFilters\SearchNotification;
use App\Service\MessageHelper;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo')]
class NotificationController extends AbstractController
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
    ) {
    }

    /**
     * @return array{FormInterface, SearchNotification, Paginator<Notification>}
     */
    private function handleSearch(Request $request, bool $fromSearchParams = false): array
    {
        /** @var User $user */
        $user = $this->getUser();
        $searchNotification = new SearchNotification($user);
        $form = $this->createForm(SearchNotificationType::class, $searchNotification);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchNotification = new SearchNotification($user);
        }
        $paginatedNotifications = $this->notificationRepository->findFilteredPaginated($searchNotification, Notification::MAX_LIST_PAGINATION);

        return [$form, $searchNotification, $paginatedNotifications];
    }

    private function getHtmlTargetContentsForNotificationAction(Request $request): array
    {
        [, $searchNotification, $paginatedNotifications] = $this->handleSearch($request, true);

        return [
            [
                'target' => '#title-list-results',
                'content' => $this->renderView('back/notifications/_title-list-results.html.twig', ['notifications' => $paginatedNotifications]),
            ],
            [
                'target' => '#table-list-results',
                'content' => $this->renderView('back/notifications/_table-list-results.html.twig', [
                    'searchNotification' => $searchNotification,
                    'notifications' => $paginatedNotifications,
                    'pages' => (int) ceil($paginatedNotifications->count() / Notification::MAX_LIST_PAGINATION),
                    'searchParams' => $request->request->get('search_params'),
                ]),
            ],
            [
                'target' => '#notification-selected-buttons',
                'content' => $this->renderView('back/notifications/_mass-action-btns.html.twig', ['searchParams' => $request->request->get('search_params')]),
            ],
        ];
    }

    #[Route('/notifications', name: 'back_notifications_list')]
    public function list(Request $request): Response
    {
        [$form, $searchNotification, $paginatedNotifications] = $this->handleSearch($request);

        return $this->render('back/notifications/index.html.twig', [
            'form' => $form,
            'searchNotification' => $searchNotification,
            'notifications' => $paginatedNotifications,
            'pages' => (int) ceil($paginatedNotifications->count() / Notification::MAX_LIST_PAGINATION),
        ]);
    }

    #[Route('/notifications/lue', name: 'back_notifications_list_read')]
    public function read(
        Request $request,
        NotificationRepository $notificationRepository,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $token = is_string($request->request->get('csrf_token')) ? $request->request->get('csrf_token') : '';
        $flashMessages = [];
        if ($request->request->get('selected_notifications') && $this->isCsrfTokenValid('mark_as_read_'.$user->getId(), $token)) {
            $notificationRepository->markUserNotificationsAsSeen($user, explode(',', (string) $request->request->get('selected_notifications')));
            $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Les notifications sélectionnées ont bien été marquées comme lues.'];
        } elseif ($this->isCsrfTokenValid('mark_as_read_'.$user->getId(), $token)) {
            $notificationRepository->markUserNotificationsAsSeen($user);
            $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Toutes les notifications ont bien été marquées comme lues.'];
        }
        if (!count($flashMessages)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }

        $htmlTargetContents = $this->getHtmlTargetContentsForNotificationAction($request);

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'htmlTargetContents' => $htmlTargetContents]);
    }

    #[Route('/notifications/supprimer', name: 'back_notifications_list_delete')]
    public function delete(
        Request $request,
        NotificationRepository $notificationRepository,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $token = is_string($request->request->get('csrf_token')) ? $request->request->get('csrf_token') : '';
        $flashMessages = [];
        if ($request->request->get('selected_notifications') && $this->isCsrfTokenValid('delete_notifications_'.$user->getId(), $token)) {
            $notificationRepository->deleteUserNotifications($user, explode(',', (string) $request->request->get('selected_notifications')));
            $flashMessages[] = ['type' => 'success', 'title' => 'Notifications supprimées', 'message' => 'Les notifications sélectionnées ont bien été supprimées.'];
        } elseif ($this->isCsrfTokenValid('delete_notifications_'.$user->getId(), $token)) {
            $notificationRepository->deleteUserNotifications($user);
            $flashMessages[] = ['type' => 'success', 'title' => 'Notifications supprimées', 'message' => 'Toutes les notifications ont bien été supprimées.'];
        }
        if (!count($flashMessages)) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }

        $htmlTargetContents = $this->getHtmlTargetContentsForNotificationAction($request);

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'htmlTargetContents' => $htmlTargetContents]);
    }

    #[Route('/notifications/{id}/supprimer', name: 'back_notifications_delete_notification')]
    public function deleteNotification(
        Notification $notification,
        NotificationRepository $notificationRepository,
        Request $request,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if ($notification->getUser()->getId() === $user->getId() && $this->isCsrfTokenValid('back_delete_notification_'.$notification->getId(), (string) $request->request->get('csrf_token'))) {
            $notificationRepository->deleteUserNotifications($user, [$notification->getId()]);
            $flashMessages[] = ['type' => 'success', 'title' => 'Notification supprimée', 'message' => 'La notification a bien été supprimée.'];
            $htmlTargetContents = $this->getHtmlTargetContentsForNotificationAction($request);

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages, 'htmlTargetContents' => $htmlTargetContents]);
        }
        $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur de suppression', 'message' => 'La notification n\'a pas pu être supprimée, veuillez réessayer..'];

        return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
    }
}
