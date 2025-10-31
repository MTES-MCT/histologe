<?php

namespace App\EventListener;

use App\Entity\Partner;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Partner::class)]
class PartnerUpdatedListener
{
    public function preUpdate(Partner $partner, PreUpdateEventArgs $eventArgs): void
    {
        if (!$eventArgs->hasChangedField('email')) {
            return;
        }

        if (null === $partner->getEmailDeliveryIssue()) {
            return;
        }

        $partner->setEmailDeliveryIssue(null);
    }
}
