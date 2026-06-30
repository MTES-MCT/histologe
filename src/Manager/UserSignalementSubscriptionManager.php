<?php

namespace App\Manager;

use App\Entity\Affectation;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\User;
use App\Entity\UserSignalementSubscription;
use App\Repository\UserRepository;
use App\Repository\UserSignalementSubscriptionRepository;
use App\Service\Notification\NotificationAndMailSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class UserSignalementSubscriptionManager
{
    public function __construct(
        private readonly NotificationAndMailSender $notificationAndMailSender,
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly UserRepository $userRepository,
        private readonly UserSignalementSubscriptionRepository $userSignalementSubscriptionRepository,
        #[Autowire(env: 'USER_SYSTEM_EMAIL')]
        private readonly string $userSystemEmail,
    ) {
    }

    public function createOrGet(
        User $userToSubscribe,
        Signalement $signalement,
        User $createdBy,
        ?Affectation $affectation = null,
        bool &$subscriptionCreated = false,
    ): ?UserSignalementSubscription {
        /** @var ?UserSignalementSubscription $subscription */
        $subscription = $this->userSignalementSubscriptionRepository->findOneBy(['user' => $userToSubscribe, 'signalement' => $signalement]);
        if (null === $subscription) {
            $subscription = (new UserSignalementSubscription())
            ->setUser($userToSubscribe)
            ->setSignalement($signalement)
            ->setCreatedBy($createdBy);

            $this->entityManager->persist($subscription);
            if ($affectation) {
                $this->notificationAndMailSender->sendNewSubscription($subscription, $affectation);
            }
            $subscriptionCreated = true;
        }

        return $subscription;
    }

    public function createDefaultSubscriptionsForAffectation(Affectation $affectation): void
    {
        $signalement = $affectation->getSignalement();
        $user = $this->security->getUser();
        /** @var ?User $createdBy */
        $createdBy = $user ?: $this->userRepository->findOneBy(['email' => $this->userSystemEmail]);
        foreach ($affectation->getPartner()->getUsers() as $userPartner) {
            if ($userPartner->isApiUser()) {
                continue;
            }
            $this->createOrGet(userToSubscribe: $userPartner, signalement: $signalement, createdBy: $createdBy, affectation: $affectation);
        }
    }

    public function doesUserNeedSubscription(
        ?User $user,
        SuiviCategory $category,
        Signalement $signalement,
    ): bool {
        if (!$user) {
            return false;
        }
        if ($user->isUsager() || $user->isApiUser() || $user->isSuperAdmin()) {
            return false;
        }
        if (in_array($category, [
            SuiviCategory::AFFECTATION_IS_ACCEPTED,
            SuiviCategory::AFFECTATION_IS_REFUSED,
            SuiviCategory::MESSAGE_USAGER,
            SuiviCategory::MESSAGE_USAGER_POST_CLOTURE,
            SuiviCategory::MESSAGE_BAILLEUR,
            SuiviCategory::DEMANDE_ABANDON_PROCEDURE,
            SuiviCategory::DEMANDE_POURSUITE_PROCEDURE,
            SuiviCategory::SIGNALEMENT_STATUS_IS_SYNCHRO,
            SuiviCategory::SIGNALEMENT_EDITED_FO,
        ])) {
            return false;
        }
        if (SignalementStatus::DRAFT === $signalement->getStatut()) {
            return false;
        }

        return true;
    }
}
