<?php

namespace App\EventListener;

use App\Entity\Partner;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Partner::class)]
#[AsDoctrineListener(event: Events::postFlush)]
class PartnerUpdatedListener
{
    private mixed $entityToFlush = null;

    public function postUpdate(Partner $partner, PostUpdateEventArgs $event): void
    {
        $unitOfWork = $event->getObjectManager()->getUnitOfWork();

        foreach ($unitOfWork->getEntityChangeSet($partner) as $key => $change) {
            if ('email' === $key) {
                $partner->setEmailDeliveryIssue(null);
                $this->entityToFlush = $partner;
                break;
            }
        }
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        if (!$this->entityToFlush instanceof Partner) {
            return;
        }

        $em = $event->getObjectManager();
        $em->persist($this->entityToFlush);

        $this->entityToFlush = null;
        $em->flush();
    }
}
