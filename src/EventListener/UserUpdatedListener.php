<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: User::class)]
class UserUpdatedListener
{
    public function preUpdate(User $user, PreUpdateEventArgs $eventArgs): void
    {
        if (!$eventArgs->hasChangedField('email')) {
            return;
        }

        if (null === $user->getEmailDeliveryIssue()) {
            return;
        }

        $user->setEmailDeliveryIssue(null);
    }
}
