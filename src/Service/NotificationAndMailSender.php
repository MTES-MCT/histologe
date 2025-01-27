<?php

namespace App\Service;

use App\Entity\Affectation;
use App\Entity\Notification;
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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class NotificationAndMailSender
{
    private $unitOfWork;
    private Signalement $signalement;
    private ?Suivi $suivi;
    private ?Affectation $affectation;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly PartnerRepository $partnerRepository,
        private readonly NotificationFactory $notificationFactory,
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly ParameterBagInterface $parameterBag,
        private readonly Security $security,
    ) {
        $this->unitOfWork = $this->entityManager->getUnitOfWork();
        $this->suivi = null;
    }

    public function sendNewSignalement(Signalement $signalement): void
    {
        $mailerType = NotificationMailerType::TYPE_SIGNALEMENT_NEW;
        $this->signalement = $signalement;
        $territory = $this->signalement->getTerritory();
        $recipients = $this->getRecipientsAdmins($territory);
        $this->send($mailerType, $recipients);
    }

    public function sendNewAffectation(Affectation $affectation): void
    {
        $mailerType = NotificationMailerType::TYPE_AFFECTATION_NEW;
        $this->affectation = $affectation;
        $this->signalement = $affectation->getSignalement();
        $recipients = $this->getRecipientsPartner($affectation->getPartner());
        $this->send($mailerType, $recipients);
    }

    private function send(NotificationMailerType $notificationMailerType, ArrayCollection $recipients, bool $isInAppNotificationCreated = false): void
    {
        if ($isInAppNotificationCreated) {
            foreach ($recipients as $user) {
                $this->createInAppNotification($user);
            }
        }

        $this->sendMail($recipients, $notificationMailerType);
    }

    private function createInAppNotification($user): void
    {
        if (empty($this->suivi) || Suivi::DESCRIPTION_SIGNALEMENT_VALIDE === $this->suivi->getDescription()) {
            return;
        }

        $notification = $this->notificationFactory->createInstanceFrom($user, $this->suivi);
        $this->entityManager->persist($notification);
        $this->unitOfWork->computeChangeSet(
            $this->entityManager->getClassMetadata(Notification::class),
            $notification
        );
    }

    private function sendMail(ArrayCollection $recipients, NotificationMailerType $mailType): void
    {
        if (!$recipients->isEmpty()) {
            $recipientsEmails = $this->getRecipientsFilteredEmail($recipients);

            if (!empty($recipientsEmails)) {
                $this->notificationMailerRegistry->send(
                    new NotificationMail(
                        type: $mailType,
                        to: $recipientsEmails,
                        territory: $this->signalement->getTerritory(),
                        signalement: $this->signalement,
                        suivi: $this->suivi,
                    )
                );
            }
        }
    }

    private function getRecipientsAdmins(?Territory $territory): ArrayCollection
    {
        $recipients = new ArrayCollection();

        $partner = $this->getPartnerFromSignalementInsee($this->signalement, $territory);
        $users = $this->userRepository->findActiveAdminsAndTerritoryAdmins($territory, $partner);

        foreach ($users as $user) {
            $recipients[] = $user;
        }

        return $recipients;
    }

    private function getRecipientsPartner(Partner $partner): ArrayCollection
    {
        $recipients = new ArrayCollection();
        if ($partner->getEmail()) {
            $recipients->add($partner);
        }

        foreach ($partner->getUsers() as $user) {
            if ($user->getIsMailingActive() && $this->isUserNotified($partner, $user)) {
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

    private function getPartnerFromSignalementInsee(Signalement $signalement, ?Territory $territory): ?Partner
    {
        $authorizedInsee = $this->parameterBag->get('authorized_codes_insee');

        if (isset($authorizedInsee[$territory->getZip()])) {
            foreach ($authorizedInsee[$territory->getZip()] as $key => $authorizedInseePartner) {
                if (\in_array($signalement->getInseeOccupant(), $authorizedInseePartner)) {
                    return $this->partnerRepository->findOneBy(['nom' => $key, 'territory' => $territory]);
                }
            }
        }

        return null;
    }

    private function isUserNotified(Partner $partner, User $user): bool
    {
        // To be notified
        // - the user must be active and not an admin
        // - if entity is Affectation
        // - if entity is Suivi: we check that the partner of the user is different from the partner of the user who created the suivi
        // TODO: activate when suivi is out of ActivityListener
        /*if ($entity instanceof Suivi) {
            $suiviPartner = $entity->getCreatedBy()?->getPartnerInTerritory($entity->getSignalement()->getTerritory());
        }*/

        return User::STATUS_ACTIVE === $user->getStatut()
            && !$user->isSuperAdmin() && !$user->isTerritoryAdmin()
            && (!empty($this->affectation)/* || ($entity->getCreatedBy() && $partner !== $suiviPartner) */);
    }
}
