<?php

namespace App\EventListener;

use App\Entity\Signalement;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Signalement::class)]
class SignalementUpdatedListener
{
    private bool $updateOccurred = false;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function postUpdate(Signalement $signalement, PostUpdateEventArgs $event): void
    {
        $unitOfWork = $event->getObjectManager()->getUnitOfWork();

        foreach ($unitOfWork->getEntityChangeSet($signalement) as $key => $change) {
            $old = $change[0];
            $new = $change[1];
            if ($old != $new) {
                $this->updateOccurred = true;
            }

            if ('mailOccupant' === $key) {
                $user = $this->userRepository->findOneBy(['email' => $new]);
                if ($user) {
                    $this->log($user, $signalement, $key);
                    $user->setEmailDeliveryIssue(null);

                    return;
                }
                $occupant = $signalement->getSignalementUsager()?->getOccupant();
                $occupant?->setEmail($new);
            }

            if ('mailDeclarant' === $key) {
                $user = $this->userRepository->findOneBy(['email' => $new]);
                if ($user) {
                    $this->log($user, $signalement, $key);
                    $user->setEmailDeliveryIssue(null);

                    return;
                }
                $declarant = $signalement->getSignalementUsager()?->getDeclarant();
                $declarant?->setEmail($new);
            }
        }
    }

    public function updateOccurred(): bool
    {
        return $this->updateOccurred;
    }

    public function log(User $user, Signalement $signalement, string $targetField = 'mailOccupant'): void
    {
        $this->logger->info(
            'Signalement updated: existing user found - skipping update of the related entity', [
                'email' => $user->getEmail(),
                'signalement_uuid' => $signalement->getUuid(),
                'target_field' => $targetField,
            ]);
    }
}
