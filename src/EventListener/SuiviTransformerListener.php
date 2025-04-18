<?php

namespace App\EventListener;

use App\Entity\Suivi;
use App\Service\SuiviTransformerService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsDoctrineListener(event: Events::postLoad)]
class SuiviTransformerListener
{
    public function __construct(
        private readonly SuiviTransformerService $suiviTransformerService,
    ) {
    }

    public function postLoad(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Suivi) {
            $entity->setSuiviTransformerService($this->suiviTransformerService);
        }
    }
}
