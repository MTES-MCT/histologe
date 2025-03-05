<?php

namespace App\Service;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\Territory;
use App\Entity\User;
use App\Factory\NotificationFactory;
use App\Repository\PartnerRepository;
use App\Repository\UserRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class NotificationAndMailSender
{
    private Signalement $signalement;
    private ?Suivi $suivi;
    private ?Affectation $affectation;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly PartnerRepository $partnerRepository,
        private readonly NotificationFactory $notificationFactory,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly Security $security,
    ) {
        $this->suivi = null;
    }

    public function sendNewSignalement(Signalement $signalement): void
    {
        $mailerType = NotificationMailerType::TYPE_SIGNALEMENT_NEW;
        $this->signalement = $signalement;
        $territory = $this->signalement->getTerritory();
        $recipients = $this->getRecipientsAdmin($territory);
        $this->sendMail($recipients, $mailerType);
    }

    public function sendNewAffectation(Affectation $affectation): void
    {
        $mailerType = NotificationMailerType::TYPE_AFFECTATION_NEW;
        $this->affectation = $affectation;
        $this->signalement = $affectation->getSignalement();
        $recipients = $this->getRecipientsPartner($affectation->getPartner());
        $this->sendMail($recipients, $mailerType);
    }

    public function sendNewSuiviToAdminsAndPartners(Suivi $suivi, bool $sendEmail): void
    {
        $mailerType = $sendEmail ? NotificationMailerType::TYPE_NEW_COMMENT_BACK : null;
        $this->suivi = $suivi;
        $this->signalement = $suivi->getSignalement();
        $territory = $this->signalement->getTerritory();

        [$partnerRecipientsMail, $partnerRecipientsInAppNotif] = $this->getRecipientsPartners(isFilteredAffectationStatus: true);

        $recipientsAdminEmail = $this->getRecipientsAdmin($territory);
        $recipientsEmail = new ArrayCollection(
            array_merge($partnerRecipientsMail->toArray(), $recipientsAdminEmail->toArray())
        );

        $recipientsAdminInAppNotif = $this->getRecipientsAdmin($territory);
        $recipientsInAppNotif = new ArrayCollection(
            array_merge($partnerRecipientsInAppNotif->toArray(), $recipientsAdminInAppNotif->toArray())
        );

        $this->sendMail($recipientsEmail, $mailerType);
        $this->createInAppNotifications($recipientsInAppNotif);
    }

    public function sendNewSuiviToUsagers(Suivi $suivi): void
    {
        $this->suivi = $suivi;
        $this->signalement = $suivi->getSignalement();
        $this->sendMailToUsagers(NotificationMailerType::TYPE_NEW_COMMENT_FRONT_TO_USAGER);
    }

    public function sendSignalementIsAcceptedToUsager(Suivi $suivi): void
    {
        $this->suivi = $suivi;
        $this->signalement = $suivi->getSignalement();
        $this->sendMailToUsagers(NotificationMailerType::TYPE_SIGNALEMENT_VALIDATION_TO_USAGER);
    }

    public function sendSignalementIsRefusedToUsager(Suivi $suivi, string $motif): void
    {
        $this->suivi = $suivi;
        $this->signalement = $suivi->getSignalement();
        $this->sendMailToUsagers(NotificationMailerType::TYPE_SIGNALEMENT_REFUSAL_TO_USAGER, $motif);
    }

    public function sendSignalementIsClosedToUsager(Suivi $suivi): void
    {
        $this->suivi = $suivi;
        $this->signalement = $suivi->getSignalement();
        $this->sendMailToUsagers(NotificationMailerType::TYPE_SIGNALEMENT_CLOSED_TO_USAGER);
    }

    public function sendSignalementIsClosedToPartners(Suivi $suivi): void
    {
        $this->suivi = $suivi;
        $this->signalement = $this->suivi->getSignalement();
        [$partnerRecipientsMail, $partnerRecipientsInAppNotif] = $this->getRecipientsPartners(isFilteredAffectationStatus: false);

        $this->sendMail($partnerRecipientsMail, NotificationMailerType::TYPE_SIGNALEMENT_CLOSED_TO_PARTNERS);
        $this->createInAppNotifications($partnerRecipientsInAppNotif);
    }

    private function createInAppNotifications(ArrayCollection $recipients)
    {
        foreach ($recipients as $user) {
            if ($user instanceof User && $user !== $this->suivi->getCreatedBy()) {
                $this->createInAppNotification($user);
            }
        }
        $this->entityManager->flush();
    }

    private function createInAppNotification($user): void
    {
        if (empty($this->suivi) || Suivi::DESCRIPTION_SIGNALEMENT_VALIDE === $this->suivi->getDescription()) {
            return;
        }

        $notification = $this->notificationFactory->createInstanceFrom($user, $this->suivi);
        $this->entityManager->persist($notification);
    }

    private function sendMailToUsagers(NotificationMailerType $mailType, ?string $motif = null): void
    {
        $recipients = new ArrayCollection($this->signalement->getMailUsagers());
        if (!$recipients->isEmpty()) {
            $recipients->removeElement($this->suivi->getCreatedBy()?->getEmail());
            foreach ($recipients as $recipient) {
                $this->notificationMailerRegistry->send(
                    new NotificationMail(
                        type: $mailType,
                        to: $recipient,
                        territory: $this->signalement->getTerritory(),
                        signalement: $this->signalement,
                        suivi: $this->suivi,
                        motif: $motif,
                    )
                );
            }
        }
    }

    private function sendMail(ArrayCollection $recipients, ?NotificationMailerType $mailType): void
    {
        if (!$recipients->isEmpty() && $mailType) {
            $recipientsEmails = $this->getRecipientsFilteredEmail($recipients);

            if (!empty($recipientsEmails)) {
                $this->notificationMailerRegistry->send(
                    new NotificationMail(
                        type: $mailType,
                        to: $recipientsEmails,
                        territory: $this->signalement->getTerritory(),
                        signalement: $this->signalement,
                        suivi: $this->suivi,
                        isRecipientVisible: false,
                    )
                );
            }
        }
    }

    private function getRecipientsAdmin(?Territory $territory): ArrayCollection
    {
        $recipients = new ArrayCollection();

        $users = $this->userRepository->findActiveAdminsAndTerritoryAdmins($territory);

        foreach ($users as $user) {
            $recipients[] = $user;
        }

        return $recipients;
    }

    private function getRecipientsPartners(bool $isFilteredAffectationStatus): array
    {
        $partnerRecipientsMail = new ArrayCollection();
        $partnerRecipientsInAppNotif = new ArrayCollection();
        foreach ($this->signalement->getAffectations() as $affectation) {
            if (!$isFilteredAffectationStatus
                    || AffectationStatus::STATUS_WAIT->value === $affectation->getStatut()
                    || AffectationStatus::STATUS_ACCEPTED->value === $affectation->getStatut()) {
                $partner = $this->partnerRepository->getWithUserPartners($affectation->getPartner());
                $partnerRecipientsMailItem = $this->getRecipientsPartner($partner);
                $partnerRecipientsInAppNotifItem = $this->getRecipientsPartner($partner, false);
                $partnerRecipientsMail = new ArrayCollection(
                    array_merge($partnerRecipientsMail->toArray(), $partnerRecipientsMailItem->toArray())
                );
                $partnerRecipientsInAppNotif = new ArrayCollection(
                    array_merge($partnerRecipientsInAppNotif->toArray(), $partnerRecipientsInAppNotifItem->toArray())
                );
            }
        }

        return [
            $partnerRecipientsMail,
            $partnerRecipientsInAppNotif,
        ];
    }

    private function getRecipientsPartner(Partner $partner, bool $filterMailingActive = true): ArrayCollection
    {
        $recipients = new ArrayCollection();
        if ($partner->getEmail()) {
            $recipients->add($partner);
        }

        foreach ($partner->getUsers() as $user) {
            if ($this->isUserNotified($partner, $user) && (!$filterMailingActive || $user->getIsMailingActive())) {
                $recipients->add($user);
            }
        }

        return $recipients;
    }

    private function getRecipientsFilteredEmail(ArrayCollection $recipients): array
    {
        /** @var ?User $user */
        $user = $this->security->getUser();
        if ($user) {
            $recipients->removeElement($user);
        }

        $recipientsEmails = [];
        foreach ($recipients as $recipientUserOrPartner) {
            if ($recipientUserOrPartner instanceof User && !$recipientUserOrPartner->getIsMailingActive()) {
                continue;
            }
            if ('' !== trim($recipientUserOrPartner->getEmail()) && null !== $recipientUserOrPartner->getEmail()) {
                $recipientsEmails[] = $recipientUserOrPartner->getEmail();
            }
        }

        $recipientsEmails = array_unique($recipientsEmails);

        return $recipientsEmails;
    }

    private function isUserNotified(Partner $partner, User $user): bool
    {
        // To be notified
        // - the user must be active and not an admin
        // - if entity is Affectation
        // - if entity is Suivi: we check that the partner of the user is different from the partner of the user who created the suivi
        $suiviPartner = null;
        if (!empty($this->suivi)) {
            $suiviPartner = $this->suivi->getCreatedBy()?->getPartnerInTerritory($this->suivi->getSignalement()->getTerritory());
        }

        return User::STATUS_ACTIVE === $user->getStatut()
            && !$user->isSuperAdmin() && !$user->isTerritoryAdmin()
            && (!empty($this->affectation) || ($this->suivi->getCreatedBy() && $partner !== $suiviPartner));
    }
}
