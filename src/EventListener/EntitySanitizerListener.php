<?php

namespace App\EventListener;

use App\Entity\Behaviour\EntitySanitizerInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
readonly class EntitySanitizerListener
{
    public function __construct(
        #[Autowire(service: 'html_sanitizer.sanitizer.app.message_sanitizer')]
        private HtmlSanitizerInterface $htmlSanitizer,
    ) {
    }

    /**
     * @param LifecycleEventArgs<EntityManager> $eventArgs
     */
    public function prePersist(LifecycleEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();
        if ($entity instanceof EntitySanitizerInterface) {
            $entity->sanitize($this->htmlSanitizer);
        }
    }

    /**
     * @param LifecycleEventArgs<EntityManager> $eventArgs
     */
    public function preUpdate(LifecycleEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();
        if ($entity instanceof EntitySanitizerInterface) {
            $entity->sanitize($this->htmlSanitizer);
        }
    }
}
