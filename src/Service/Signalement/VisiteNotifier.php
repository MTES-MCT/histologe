<?php

namespace App\Service\Signalement;

use App\Entity\Affectation;
use App\Entity\Enum\Qualification;
use App\Entity\Intervention;
use App\Entity\Suivi;
use App\Entity\User;
use App\Factory\NotificationFactory;
use App\Factory\SuiviFactory;
use App\Manager\SuiviManager;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;

class VisiteNotifier
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SuiviFactory $suiviFactory,
        private SuiviManager $suiviManager,
        private NotificationFactory $notificationFactory,
        private NotificationMailerRegistry $notificationMailerRegistry,
        private UserRepository $userRepository,
    ) {
    }

    public function notifyUsagers(Intervention $intervention, NotificationMailerType $notificationMailerType, ?DateTimeInterface $previousDate = null): void
    {
        $toRecipients = new ArrayCollection($intervention->getSignalement()->getMailUsagers());
        if (!$toRecipients->isEmpty()) {
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
    }

    public function notifyAgents(
        ?Intervention $intervention,
        Suivi $suivi,
        ?User $currentUser,
        ?NotificationMailerType $notificationMailerType,
        bool $notifyAdminTerritory = true,
        ?Affectation $affectation = null,
    ): void {
        if ($intervention) {
            if ($notifyAdminTerritory) {
                $listUsersTerritoryAdmin = $this->userRepository->findActiveTerritoryAdmins($intervention->getSignalement()->getTerritory());
                $listUsersPartner = $intervention->getPartner()->getUsers();
                $listUsersToNotify = array_unique(array_merge($listUsersTerritoryAdmin, $listUsersPartner->toArray()), \SORT_REGULAR);
            } else {
                $listUsersToNotify = $intervention->getPartner()->getUsers();
            }
        } else {
            $listUsersToNotify = $affectation->getPartner()->getUsers();
        }
        foreach ($listUsersToNotify as $user) {
            if ($user != $currentUser) {
                $this->notifyAgent($user, $intervention, $suivi, $notificationMailerType, $affectation);
            }
        }
    }

    private function notifyAgent(
        User $user,
        ?Intervention $intervention,
        Suivi $suivi,
        ?NotificationMailerType $notificationMailerType,
        ?Affectation $affectation = null,
    ) {
        if ($notificationMailerType) {
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

        $notification = $this->notificationFactory->createInstanceFrom($user, $suivi);
        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    public function notifyVisiteToConclude(Intervention $intervention): int
    {
        $signalement = $intervention->getSignalement();
        $listUsersToNotify = $this->userRepository->findActiveTerritoryAdmins($signalement->getTerritory());
        $affectations = $signalement->getAffectations();
        foreach ($affectations as $affectation) {
            if ($affectation->getPartner()->hasCompetence(Qualification::VISITES)) {
                $listUsersPartner = $affectation->getPartner()->getUsers();
                $listUsersToNotify = array_unique(array_merge($listUsersToNotify, $listUsersPartner->toArray()), \SORT_REGULAR);
            }
        }

        foreach ($listUsersToNotify as $user) {
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

        return \count($listUsersToNotify);
    }
}
