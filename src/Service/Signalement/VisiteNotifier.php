<?php

namespace App\Service\Signalement;

use App\Entity\Intervention;
use App\Entity\Signalement;
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

    /**
     * Creates a suivi corresponding to a Visite
     */
    public function createSuivi(string $description, User $currentUser, Signalement $signalement): Suivi
    {
        $suivi = $this->suiviFactory->createInstanceFrom(
            user: $currentUser,
            signalement: $signalement,
            params: [
                'type' => SUIVI::TYPE_AUTO,
                'description' => $description,
            ],
            isPublic: true,
            context: Suivi::CONTEXT_INTERVENTION,
        );
        $this->suiviManager->save($suivi);

        return $suivi;
    }

    /**
     * Send emails to usagers about a Visite
     */
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

    /**
     * Send emails and notifications to agents about a Visite
     */
    public function notifyAgents(Intervention $intervention, Suivi $suivi, User $currentUser, ?NotificationMailerType $notificationMailerType): void
    {
        // Territory admins
        $listUsersTerritoryAdmin = $this->userRepository->findActiveTerritoryAdmins($intervention->getSignalement()->getTerritory());
        $listUsersPartner = $intervention->getPartner()->getUsers();
        $listUsersToNotify = array_unique(array_merge($listUsersTerritoryAdmin, $listUsersPartner->toArray()), SORT_REGULAR);
        foreach ($listUsersToNotify as $user) {
            if ($user != $currentUser) {
                $this->notifyAgent($user, $intervention, $suivi, $notificationMailerType);
            }
        }
    }

    private function notifyAgent(User $user, Intervention $intervention, Suivi $suivi, ?NotificationMailerType $notificationMailerType)
    {
        if ($notificationMailerType) {
            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: $notificationMailerType,
                    to: $user->getEmail(),
                    territory: $intervention->getSignalement()->getTerritory(),
                    signalement: $intervention->getSignalement(),
                    intervention: $intervention,
                )
            );
        }

        $notification = $this->notificationFactory->createInstanceFrom($user, $suivi);
        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }
}
