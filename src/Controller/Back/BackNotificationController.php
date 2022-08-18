<?php

namespace App\Controller\Back;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo')]
class BackNotificationController extends AbstractController
{
    private ArrayCollection $signalements;
    private ArrayCollection $affectations;
    private ArrayCollection $suivis;

    public function __construct()
    {
        $this->suivis = new ArrayCollection();
        $this->affectations = new ArrayCollection();
        $this->signalements = new ArrayCollection();
    }

    #[Route('/news', name: 'back_news_activities')]
    public function newsActivitiesSinceLastLogin(Request $request, NotificationRepository $notificationRepository, EntityManagerInterface $entityManager): Response
    {
        $title = 'Administration - Nouveauté(s)';
        if ($this->isCsrfTokenValid('mark_as_read_'.$this->getUser()->getId(), $request->get('mark_as_read'))) {
            $this->markAllAsRead($entityManager);
            $this->addFlash('success', 'Toutes les notifications marquées comme lues.');

            return $this->redirectToRoute('back_news_activities');
        } elseif ($this->isCsrfTokenValid('delete_all_notifications_'.$this->getUser()->getId(), $request->get('delete_all_notifications'))) {
            $this->deleteAllNotifications($entityManager);
            $this->addFlash('success', 'Toutes les notifications ont été supprimées.');

            return $this->redirectToRoute('back_news_activities');
        }
        $notifications = new ArrayCollection($notificationRepository->findAllForUser($this->getUser()));
        $notifications->filter(function (Notification $notification) {
            if (Notification::TYPE_AFFECTATION === $notification->getType() && $notification->getAffectation()) {
                $this->affectations->add($notification);
            } elseif (Notification::TYPE_SUIVI === $notification->getType() && $notification->getSuivi()) {
                $this->suivis->add($notification);
            } elseif (Notification::TYPE_NEW_SIGNALEMENT === $notification->getType() && $notification->getSignalement()) {
                $this->signalements->add($notification);
            }
        });

        return $this->render('back/notifications/index.html.twig', [
            'title' => $title,
            'suivis' => $this->suivis,
            'affectations' => $this->affectations,
            'signalements' => $this->signalements,
        ]);
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

    #[Route('/news/{id}/delete', name: 'back_news_activities_delete_notification')]
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

        return $this->redirectToRoute('back_news_activities');
    }
}
