<?php

namespace App\Service\Signalement;

use App\Entity\Affectation;
use App\Entity\Enum\NotificationType;
use App\Entity\Enum\UserStatus;
use App\Entity\Intervention;
use App\Entity\Suivi;
use App\Entity\User;
use App\Factory\NotificationFactory;
use App\Repository\UserRepository;
use App\Repository\UserSignalementSubscriptionRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\NotificationAndMailSender;
use Doctrine\ORM\EntityManagerInterface;

class VisiteNotifier
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationFactory $notificationFactory,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly UserRepository $userRepository,
        private readonly NotificationAndMailSender $notificationAndMailSender,
        private readonly UserSignalementSubscriptionRepository $userSignalementSubscriptionRepository,
    ) {
    }

    public function notifyUsagers(
        Intervention $intervention,
        NotificationMailerType $notificationMailerType,
        Suivi $suivi,
        ?\DateTimeImmutable $previousDate = null,
    ): void {
        $toRecipients = $intervention->getSignalement()->getMailUsagers();
        $this->notificationAndMailSender->createInAppUsagersNotifications($suivi);
        foreach ($toRecipients as $toRecipient) {
            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: $notificationMailerType,
                    to: $toRecipient,
                    territory: $intervention->getSignalement()->getTerritory(),
                    signalement: $intervention->getSignalement(),
                    intervention: $intervention,
                    previousVisiteDate: $previousDate,
                )
            );
        }
    }

    public function notifySubscribers(
        NotificationMailerType $notificationMailerType,
        Intervention $intervention,
        Suivi $suivi,
        ?User $currentUser = null,
    ): void {
        $listUsersToNotify = $this->userRepository->findUsersSubscribedToSignalement($intervention->getSignalement());
        foreach ($listUsersToNotify as $user) {
            if ($user === $currentUser) {
                continue;
            }
            $this->notifyAgent(
                user: $user,
                suivi: $suivi,
                intervention: $intervention,
                notificationMailerType: $notificationMailerType
            );
        }
    }

    public function notifyInAppSubscribers(
        Intervention $intervention,
        Suivi $suivi,
        ?User $currentUser = null,
    ): void {
        $listUsersToNotify = $this->userRepository->findUsersSubscribedToSignalement($intervention->getSignalement());
        foreach ($listUsersToNotify as $user) {
            if ($user === $currentUser) {
                continue;
            }
            $this->notifyAgent(user: $user, suivi: $suivi, intervention: $intervention);
        }
    }

    public function notifyInterventionSubscribers(
        NotificationMailerType $notificationMailerType,
        Intervention $intervention,
    ): void {
        $subs = $this->userSignalementSubscriptionRepository->findForIntervention($intervention);
        foreach ($subs as $subscription) {
            $this->notifyAgent(
                user: $subscription->getUser(),
                intervention: $intervention,
                notificationMailerType: $notificationMailerType
            );
        }
    }

    public function notifyAffectationSubscribers(
        NotificationMailerType $notificationMailerType,
        Affectation $affectation,
        Suivi $suivi,
    ): void {
        $subs = $this->userSignalementSubscriptionRepository->findForAffectation($affectation);
        foreach ($subs as $subscription) {
            $this->notifyAgent(
                user: $subscription->getUser(),
                suivi: $suivi,
                notificationMailerType: $notificationMailerType,
                affectation: $affectation
            );
        }
    }

    private function notifyAgent(
        User $user,
        ?Suivi $suivi = null,
        ?Intervention $intervention = null,
        ?NotificationMailerType $notificationMailerType = null,
        ?Affectation $affectation = null,
    ): void {
        if (UserStatus::ARCHIVE === $user->getStatut()) {
            return;
        }
        if ($notificationMailerType) {
            if ($user->getIsMailingActive()) {
                $this->notificationMailerRegistry->send(
                    new NotificationMail(
                        type: $notificationMailerType,
                        to: $user->getEmail(),
                        territory: $intervention ? $intervention->getSignalement()->getTerritory() : $affectation->getSignalement()->getTerritory(),
                        signalement: $intervention ? $intervention->getSignalement() : $affectation->getSignalement(),
                        intervention: $intervention,
                    )
                );
            }
        }
        if ($suivi) {
            $notification = $this->notificationFactory->createInstanceFrom(
                user: $user,
                type: NotificationType::NOUVEAU_SUIVI,
                suivi: $suivi
            );
            $this->entityManager->persist($notification);
            $this->entityManager->flush();
        }
    }

    public function notifyVisiteToConclude(Intervention $intervention): int
    {
        $signalement = $intervention->getSignalement();
        $listUsersToNotify = $this->userRepository->findActiveTerritoryAdmins(
            $signalement->getTerritory()->getId(), $signalement->getInseeOccupant()
        );

        foreach ($listUsersToNotify as $user) {
            if ($user->getIsMailingActive() && UserStatus::ACTIVE === $user->getStatut()) {
                $this->notificationMailerRegistry->send(
                    new NotificationMail(
                        type: NotificationMailerType::TYPE_VISITE_PAST_REMINDER_TO_PARTNER,
                        to: $user->getEmail(),
                        territory: $signalement->getTerritory(),
                        signalement: $signalement,
                        intervention: $intervention,
                    )
                );
            }
        }

        return \count($listUsersToNotify);
    }
}
