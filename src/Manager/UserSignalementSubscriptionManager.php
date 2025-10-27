<?php

namespace App\Manager;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Entity\User;
use App\Entity\UserSignalementSubscription;
use App\Repository\UserRepository;
use App\Service\NotificationAndMailSender;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class UserSignalementSubscriptionManager extends AbstractManager
{
    public function __construct(
        ManagerRegistry $managerRegistry,
        private readonly NotificationAndMailSender $notificationAndMailSender,
        private readonly Security $security,
        private readonly UserRepository $userRepository,
        #[Autowire(env: 'USER_SYSTEM_EMAIL')]
        private readonly string $userSystemEmail,
        protected string $entityName = UserSignalementSubscription::class,
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    public function createOrGet(
        User $userToSubscribe,
        Signalement $signalement,
        User $createdBy,
        ?Affectation $affectation = null,
        bool &$subscriptionCreated = false,
    ): ?UserSignalementSubscription {
        /** @var ?UserSignalementSubscription $subscription */
        $subscription = $this->findOneBy(['user' => $userToSubscribe, 'signalement' => $signalement]);
        if (null === $subscription) {
            $subscription = (new UserSignalementSubscription())
            ->setUser($userToSubscribe)
            ->setSignalement($signalement)
            ->setCreatedBy($createdBy);

            $this->persist($subscription);
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
}
