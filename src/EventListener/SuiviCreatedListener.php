<?php

namespace App\EventListener;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Service\Signalement\SuiviHelper;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
class SuiviCreatedListener
{
    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $entityManager = $eventArgs->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        /** @var Suivi $entity */
        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            if ($this->supports($entity)) {
                $signalement = $entity->getSignalement();
                $signalement->setLastSuiviAt($entity->getCreatedAt());
                $signalement->setLastSuiviBy(SuiviHelper::getLastLabelFromSuivi($entity));
                $metaData = $entityManager->getClassMetadata(Signalement::class);
                $entityManager->persist($signalement);
                //  used to recompute the changes of a specific signalement entity
                $unitOfWork->recomputeSingleEntityChangeSet($metaData, $signalement);
            }
        }
    }

    public function supports($entity): bool
    {
        return $entity instanceof Suivi;
    }
}
