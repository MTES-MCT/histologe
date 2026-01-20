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
use App\Entity\UserSignalementSubscription;
use App\Factory\NotificationFactory;
use App\Repository\UserRepository;
use App\Service\InjonctionBailleur\CourrierBailleurGenerator;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class NotificationAndMailSender
{
    private ?Signalement $signalement = null;
    private ?Suivi $suivi = null;
    private ?Affectation $affectation = null;
    private ?User $user = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly NotificationFactory $notificationFactory,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly Security $security,
        private readonly CourrierBailleurGenerator $courrierBailleurGenerator,
    ) {
        $this->suivi = null;
        $user = $this->security->getUser();
        if ($user instanceof User) {
            $this->user = $user;
        }
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

    public function sendNewSignalementInjonction(Signalement $signalement): void
    {
        $mailerType = NotificationMailerType::TYPE_CONFIRM_INJONCTION_TO_BAILLEUR;
        $this->signalement = $signalement;
        $mailProprio = $signalement->getMailProprio();

        if ($mailProprio) {
            $pdfContent = $this->courrierBailleurGenerator->generate($signalement);

            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: $mailerType,
                    to: $mailProprio,
                    territory: $this->signalement->getTerritory(),
                    signalement: $this->signalement,
                    attachment: $pdfContent,
                )
            );
        }
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

    public function sendNewSubscription(UserSignalementSubscription $subscription, Affectation $affectation): void
    {
        if ($subscription->getUser() !== $subscription->getCreatedBy()) {
            $currentUser = $subscription->getCreatedBy();
            $this->signalement = $affectation->getSignalement();
            $description = sprintf(
                '%s vous a attribué le dossier #%s. Vous recevrez les mises à jour pour ce dossier.',
                $currentUser->getNomComplet(),
                $this->signalement->getReference()
            );
            $recipients = new ArrayCollection();
            $recipients->add($subscription->getUser());
            // pas d'email unitaire pour ce type de notification (notification email uniquement pour les agents ayant l'option isMailingSummary)
            $this->createInAppNotifications(
                recipients: $recipients,
                type: NotificationType::NOUVEL_ABONNEMENT,
                affectation: $affectation,
                description: $description
            );
        }
    }

    public function sendAffectationClosed(Affectation $affectation): void
    {
        $mailerType = NotificationMailerType::TYPE_SIGNALEMENT_CLOSED_TO_PARTNER;
        $this->affectation = $affectation;
        $this->signalement = $affectation->getSignalement();
        $userList = $this->userRepository->findUsersSubscribedToSignalement($this->signalement);

        $recipients = new ArrayCollection($userList);
        $this->sendMail($recipients, $mailerType);
        $this->createInAppNotifications(recipients: $recipients, type: NotificationType::CLOTURE_PARTENAIRE, affectation: $affectation);
    }

    public function sendNewSuiviToAdminsAndPartners(Suivi $suivi, bool $sendEmail): void
    {
        $mailerType = $sendEmail ? NotificationMailerType::TYPE_NEW_COMMENT_BACK : null;
        $this->suivi = $suivi;
        $this->signalement = $suivi->getSignalement();
        $userList = $this->userRepository->findUsersSubscribedToSignalement($this->signalement);
        $adminList = $this->userRepository->findActiveAdmins();
        $partnerList = $this->getPartnersWithEmailNotifiable(isFilteredAffectationStatus: true);
        $recipients = new ArrayCollection(array_merge($userList, $adminList, $partnerList));

        $this->sendMail($recipients, $mailerType, $suivi);
        $this->createInAppNotifications(recipients: $recipients, type: NotificationType::NOUVEAU_SUIVI, suivi: $suivi);
    }

    public function sendDemandeAbandonProcedureToAdminsAndPartners(Suivi $suivi): void
    {
        $this->suivi = $suivi;
        $this->signalement = $suivi->getSignalement();

        $listRT = $this->userRepository->findUsersSubscribedToSignalement(signalement: $this->signalement, onlyRT: true);
        $listAdmins = $this->userRepository->findActiveAdmins();
        $recipients = new ArrayCollection(array_merge($listRT, $listAdmins));

        $this->sendMail($recipients, NotificationMailerType::TYPE_DEMANDE_ABANDON_PROCEDURE_TO_ADMIN);
        $this->createInAppNotifications(recipients: $recipients, type: NotificationType::NOUVEAU_SUIVI, suivi: $suivi);
    }

    public function sendNewSuiviToUsagers(Suivi $suivi): void
    {
        $this->suivi = $suivi;
        $this->signalement = $suivi->getSignalement();
        $this->sendMailToUsagers(NotificationMailerType::TYPE_NEW_COMMENT_FRONT_TO_USAGER);
        $this->createInAppUsagersNotifications(suivi: $suivi);
    }

    public function sendDemandeAbandonProcedureToUsager(Suivi $suivi): void
    {
        $this->suivi = $suivi;
        $this->signalement = $suivi->getSignalement();
        if ($this->suivi->getCreatedBy()) {
            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_DEMANDE_ABANDON_PROCEDURE_TO_USAGER,
                    to: $this->suivi->getCreatedBy()->getEmail(),
                    territory: $this->signalement->getTerritory(),
                    signalement: $this->signalement,
                    suivi: $this->suivi,
                    motif: null,
                )
            );
        }

        if ($this->signalement->isTiersDeclarant()) {
            $recipients = new ArrayCollection($this->signalement->getMailUsagers());
            if (!$recipients->isEmpty()) {
                $recipients->removeElement($this->suivi->getCreatedBy()?->getEmail());
                foreach ($recipients as $recipient) {
                    $this->notificationMailerRegistry->send(
                        new NotificationMail(
                            type: NotificationMailerType::TYPE_DEMANDE_ABANDON_PROCEDURE_TO_OTHER_USAGER,
                            to: $recipient,
                            territory: $this->signalement->getTerritory(),
                            signalement: $this->signalement,
                            suivi: $this->suivi,
                            motif: null,
                            params: ['demandeur_abandon' => $this->suivi->getCreatedBy()?->getNomComplet()]
                        )
                    );
                }
            }
        }

        $this->createInAppUsagersNotifications(suivi: $suivi); // TODO : laisse ?
    }

    public function sendSignalementIsAcceptedToUsager(Suivi $suivi): void
    {
        $this->suivi = $suivi;
        $this->signalement = $suivi->getSignalement();
        $this->sendMailToUsagers(NotificationMailerType::TYPE_SIGNALEMENT_VALIDATION_TO_USAGER);
        $this->createInAppUsagersNotifications(suivi: $suivi);
    }

    public function sendSignalementIsRefusedToUsager(Suivi $suivi, string $motif): void
    {
        $this->suivi = $suivi;
        $this->signalement = $suivi->getSignalement();
        $this->sendMailToUsagers(NotificationMailerType::TYPE_SIGNALEMENT_REFUSAL_TO_USAGER, $motif);
        $this->createInAppUsagersNotifications(suivi: $suivi);
    }

    public function sendSignalementIsClosedToUsager(Suivi $suivi): void
    {
        $this->suivi = $suivi;
        $this->signalement = $suivi->getSignalement();
        $this->sendMailToUsagers(NotificationMailerType::TYPE_SIGNALEMENT_CLOSED_TO_USAGER);
        $this->createInAppUsagersNotifications(suivi: $suivi);
    }

    public function sendSignalementIsClosedToPartners(Suivi $suivi): void
    {
        $this->suivi = $suivi;
        $this->signalement = $this->suivi->getSignalement();
        $userList = $this->userRepository->findUsersSubscribedToSignalement($this->signalement);
        $adminList = $this->userRepository->findActiveAdmins();
        $partnerList = $this->getPartnersWithEmailNotifiable(isFilteredAffectationStatus: false);
        $recipients = new ArrayCollection(array_merge($userList, $adminList, $partnerList));

        $this->sendMail($recipients, NotificationMailerType::TYPE_SIGNALEMENT_CLOSED_TO_PARTNERS);
        $this->createInAppNotifications(recipients: $recipients, type: NotificationType::CLOTURE_SIGNALEMENT, suivi: $suivi);
    }

    /**
     * @param ArrayCollection<int, User> $recipients
     */
    private function createInAppNotifications(
        ArrayCollection $recipients,
        NotificationType $type,
        ?Suivi $suivi = null,
        ?Affectation $affectation = null,
        ?Signalement $signalement = null,
        ?string $description = null,
    ): void {
        foreach ($recipients as $user) {
            if (!($user instanceof User)) {
                continue;
            }
            if (in_array($type, [NotificationType::NOUVEAU_SUIVI, NotificationType::CLOTURE_SIGNALEMENT]) && $user === $suivi->getCreatedBy()) {
                continue;
            }
            $this->createInAppNotification(
                user: $user,
                type: $type,
                suivi: $suivi,
                affectation: $affectation,
                signalement: $signalement,
                description: $description
            );
        }
        $this->entityManager->flush();
    }

    private function createInAppNotification(
        User $user,
        NotificationType $type,
        ?Suivi $suivi = null,
        ?Affectation $affectation = null,
        ?Signalement $signalement = null,
        ?string $description = null,
    ): void {
        if (NotificationType::NOUVEAU_SUIVI === $type) {
            if (Suivi::DESCRIPTION_SIGNALEMENT_VALIDE === $this->suivi->getDescription()) {
                return;
            }
        }
        $notification = $this->notificationFactory->createInstanceFrom(
            user: $user,
            type: $type,
            suivi: $suivi,
            affectation: $affectation,
            signalement: $signalement,
            description: $description
        );
        if ($affectation) {
            $this->entityManager->persist($affectation);
        }
        $this->entityManager->persist($notification);
    }

    private function sendMailToUsagers(NotificationMailerType $mailType, ?string $motif = null): void
    {
        if ($this->signalement->getIsLogementVacant()) {
            return;
        }
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

    /**
     * @param ArrayCollection<int, mixed> $recipients
     */
    private function sendMail(ArrayCollection $recipients, ?NotificationMailerType $mailType, ?Suivi $suivi = null): void
    {
        if (!$recipients->isEmpty() && $mailType) {
            $recipientsEmails = $this->getRecipientsFilteredEmail($recipients, $suivi);

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

    /**
     * @return ArrayCollection<int, User>
     */
    private function getRecipientsAdmin(?Territory $territory): ArrayCollection
    {
        $recipients = new ArrayCollection();

        $users = $this->userRepository->findActiveAdminsAndTerritoryAdmins($territory);

        foreach ($users as $user) {
            $recipients->add($user);
        }

        return $recipients;
    }

    /**
     * @return array<int, Partner>
     */
    private function getPartnersWithEmailNotifiable(bool $isFilteredAffectationStatus): array
    {
        $partners = [];
        foreach ($this->signalement->getAffectations() as $affectation) {
            if (!$isFilteredAffectationStatus || AffectationStatus::WAIT === $affectation->getStatut() || AffectationStatus::ACCEPTED === $affectation->getStatut()) {
                $partner = $affectation->getPartner();
                if ($partner->getEmail() && $partner->isEmailNotifiable()) {
                    // on ne notifie pas l'email générique du partenaire si l'utilisateur courant en fait partie
                    if (!$this->user || !$this->user->hasPartner($partner)) {
                        $partners[] = $partner;
                    }
                }
            }
        }

        return $partners;
    }

    /**
     * @return ArrayCollection<int, mixed>
     */
    private function getRecipientsPartner(Partner $partner): ArrayCollection
    {
        $recipients = new ArrayCollection();
        if ($partner->getEmail() && $partner->isEmailNotifiable()) {
            // on ne notifie pas l'email générique du partenaire si l'utilisateur courant en fait partie
            if (!$this->user || !$this->user->hasPartner($partner)) {
                $recipients->add($partner);
            }
        }

        foreach ($partner->getUsers() as $user) {
            if ($this->isUserNotified($partner, $user)) {
                $recipients->add($user);
            }
        }

        return $recipients;
    }

    /**
     * @param ArrayCollection<int, mixed> $recipients
     *
     * @return array<int, string>
     */
    private function getRecipientsFilteredEmail(ArrayCollection $recipients, ?Suivi $suivi = null): array
    {
        $copyRecipients = clone $recipients;
        if ($this->user) {
            $copyRecipients->removeElement($this->user);
        } elseif ($suivi && $suivi->getCreatedBy()) {
            $copyRecipients->removeElement($suivi->getCreatedBy());
        }

        $recipientsEmails = [];
        foreach ($copyRecipients as $recipientUserOrPartner) {
            if ($recipientUserOrPartner instanceof User) {
                if (!$recipientUserOrPartner->getIsMailingActive()) {
                    continue;
                }
                if ($recipientUserOrPartner->getIsMailingSummary()) {
                    continue;
                }
            }
            if (null !== $recipientUserOrPartner->getEmail() && '' !== mb_trim($recipientUserOrPartner->getEmail())) {
                $recipientsEmails[] = $recipientUserOrPartner->getEmail();
            }
        }

        return array_unique($recipientsEmails);
    }

    private function isUserNotified(Partner $partner, User $user): bool
    {
        // To be notified
        // - the user must be active and not an admin
        // - if entity is Affectation
        // - if entity is Suivi: we check that the partner of the user is different from the partner of the user who created the suivi
        $suiviPartner = null;
        if (!empty($this->suivi)) {
            $suiviPartner = $this->suivi->getPartner();
        }

        return UserStatus::ACTIVE === $user->getStatut()
            && !$user->isSuperAdmin() && !$user->isTerritoryAdmin()
            && (!empty($this->affectation) || ($this->suivi->getCreatedBy() && $partner !== $suiviPartner));
    }

    public function createInAppUsagersNotifications(Suivi $suivi): void
    {
        $this->signalement = $suivi->getSignalement();
        $usagers = $this->signalement->getUsagers();
        $recipients = new ArrayCollection($usagers);
        $this->createInAppNotifications(recipients: $recipients, type: NotificationType::SUIVI_USAGER, suivi: $suivi);
    }
}
