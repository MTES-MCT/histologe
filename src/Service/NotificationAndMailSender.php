<?php

namespace App\Service;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\NotificationType;
use App\Entity\Enum\UserStatus;
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
use Symfony\Component\DependencyInjection\Attribute\Autowire;

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
        #[Autowire(env: 'FEATURE_EMAIL_RECAP')]
        private readonly bool $featureEmailRecap,
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
        $this->createInAppNotifications(recipients: $recipients, type: NotificationType::NOUVEAU_SIGNALEMENT, signalement: $signalement);
    }

    public function sendNewAffectation(Affectation $affectation): void
    {
        $mailerType = NotificationMailerType::TYPE_AFFECTATION_NEW;
        $this->affectation = $affectation;
        $this->signalement = $affectation->getSignalement();
        $recipients = $this->getRecipientsPartner($affectation->getPartner());
        $this->sendMail($recipients, $mailerType);
        $this->createInAppNotifications(recipients: $recipients, type: NotificationType::NOUVELLE_AFFECTATION, affectation: $affectation);
    }

    public function sendAffectationClosed(Affectation $affectation, User $user): void
    {
        $mailerType = NotificationMailerType::TYPE_SIGNALEMENT_CLOSED_TO_PARTNER;
        $this->affectation = $affectation;
        $this->signalement = $affectation->getSignalement();
        $partnerToExclude = $user->getPartnerInTerritoryOrFirstOne($this->signalement->getTerritory());
        $userList = $this->userRepository->findUsersAffectedToSignalement($this->signalement, $partnerToExclude);
        $recipients = new ArrayCollection($userList);
        $this->sendMail($recipients, $mailerType);
        $this->createInAppNotifications(recipients: $recipients, type: NotificationType::CLOTURE_PARTENAIRE, affectation: $affectation);
    }

    public function sendNewSuiviToAdminsAndPartners(Suivi $suivi, bool $sendEmail): void
    {
        $mailerType = $sendEmail ? NotificationMailerType::TYPE_NEW_COMMENT_BACK : null;
        $this->suivi = $suivi;
        $this->signalement = $suivi->getSignalement();
        $territory = $this->signalement->getTerritory();

        $partnerRecipients = $this->getRecipientsPartners(isFilteredAffectationStatus: true);
        $adminRecipients = $this->getRecipientsAdmin($territory);
        $recipients = new ArrayCollection(
            array_merge($partnerRecipients->toArray(), $adminRecipients->toArray())
        );

        $this->sendMail($recipients, $mailerType);
        $this->createInAppNotifications(recipients: $recipients, type: NotificationType::NOUVEAU_SUIVI, suivi: $suivi);
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
        $partnerRecipients = $this->getRecipientsPartners(isFilteredAffectationStatus: false);

        $this->sendMail($partnerRecipients, NotificationMailerType::TYPE_SIGNALEMENT_CLOSED_TO_PARTNERS);

        $adminRecipients = $this->getRecipientsAdmin(null);
        $recipientsInAppNotif = new ArrayCollection(
            array_merge($partnerRecipients->toArray(), $adminRecipients->toArray())
        );
        $this->createInAppNotifications(recipients: $recipientsInAppNotif, type: NotificationType::CLOTURE_SIGNALEMENT, suivi: $suivi);
    }

    private function createInAppNotifications(ArrayCollection $recipients, NotificationType $type, ?Suivi $suivi = null, ?Affectation $affectation = null, ?Signalement $signalement = null): void
    {
        foreach ($recipients as $user) {
            if (!($user instanceof User)) {
                continue;
            }
            if (in_array($type, [NotificationType::NOUVEAU_SUIVI, NotificationType::CLOTURE_SIGNALEMENT]) && $user === $suivi->getCreatedBy()) {
                continue;
            }
            $this->createInAppNotification(user: $user, type: $type, suivi: $suivi, affectation: $affectation, signalement: $signalement);
        }
        $this->entityManager->flush();
    }

    private function createInAppNotification($user, NotificationType $type, ?Suivi $suivi = null, ?Affectation $affectation = null, ?Signalement $signalement = null): void
    {
        if (!$this->featureEmailRecap && !in_array($type, [NotificationType::NOUVEAU_SUIVI, NotificationType::CLOTURE_SIGNALEMENT])) {
            return;
        }
        if (NotificationType::NOUVEAU_SUIVI === $type) {
            if (Suivi::DESCRIPTION_SIGNALEMENT_VALIDE === $this->suivi->getDescription()) {
                return;
            }
        }
        $notification = $this->notificationFactory->createInstanceFrom(user: $user, type: $type, suivi: $suivi, affectation: $affectation, signalement: $signalement);
        if ($affectation) {
            $this->entityManager->persist($affectation);
        }
        $this->entityManager->persist($notification);
    }

    private function sendMailToUsagers(NotificationMailerType $mailType, ?string $motif = null): void
    {
        $recipients = new ArrayCollection($this->signalement->getMailUsagers());
        if (!$recipients->isEmpty()) {
            $recipients->removeElement($this->suivi->getCreatedBy()?->getEmail());
            foreach ($recipients as $recipient) {
                if ($this->signalement->isTiersDeclarant() && $recipient === $this->signalement->getMailDeclarant()) {
                    $agentExist = $this->userRepository->findAgentByEmail(email: $recipient, userStatus: UserStatus::ACTIVE, acceptRoleApi: false);
                    if ($agentExist) {
                        continue;
                    }
                }
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

    private function getRecipientsPartners(bool $isFilteredAffectationStatus): ArrayCollection
    {
        $partnerRecipientsMail = new ArrayCollection();
        foreach ($this->signalement->getAffectations() as $affectation) {
            if (!$isFilteredAffectationStatus || AffectationStatus::STATUS_WAIT->value === $affectation->getStatut()
                    || AffectationStatus::STATUS_ACCEPTED->value === $affectation->getStatut()) {
                $partner = $this->partnerRepository->getWithUserPartners($affectation->getPartner());
                $partnerRecipientsMailItem = $this->getRecipientsPartner($partner);
                $partnerRecipientsMail = new ArrayCollection(
                    array_merge($partnerRecipientsMail->toArray(), $partnerRecipientsMailItem->toArray())
                );
            }
        }

        return $partnerRecipientsMail;
    }

    private function getRecipientsPartner(Partner $partner): ArrayCollection
    {
        $recipients = new ArrayCollection();
        if ($partner->getEmail() && $partner->isEmailNotifiable()) {
            $recipients->add($partner);
        }

        foreach ($partner->getUsers() as $user) {
            if ($this->isUserNotified($partner, $user)) {
                $recipients->add($user);
            }
        }

        return $recipients;
    }

    private function getRecipientsFilteredEmail(ArrayCollection $recipients): array
    {
        $copyRecipients = clone $recipients;
        /** @var ?User $user */
        $user = $this->security->getUser();
        if ($user) {
            $copyRecipients->removeElement($user);
        }

        $recipientsEmails = [];
        foreach ($copyRecipients as $recipientUserOrPartner) {
            if ($recipientUserOrPartner instanceof User) {
                if (!$recipientUserOrPartner->getIsMailingActive()) {
                    continue;
                }
                if ($this->featureEmailRecap && $recipientUserOrPartner->getIsMailingSummary()) {
                    continue;
                }
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

        return UserStatus::ACTIVE === $user->getStatut()
            && !$user->isSuperAdmin() && !$user->isTerritoryAdmin()
            && (!empty($this->affectation) || ($this->suivi->getCreatedBy() && $partner !== $suiviPartner));
    }
}
