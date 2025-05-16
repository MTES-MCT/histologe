<?php

namespace App\EventListener;

use App\Entity\Behaviour\EntitySanitizerInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
readonly class EntitySanitizerListener
{
    public function __construct(
        #[Autowire(service: 'html_sanitizer.sanitizer.app.message_sanitizer')]
        private HtmlSanitizerInterface $htmlSanitizer,
        private LoggerInterface $logger,
    ) {
    }

    public function prePersist(LifecycleEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();
        if ($entity instanceof EntitySanitizerInterface) {
            $this->sanitize($entity, 'prePersist');
        }
    }

    public function preUpdate(LifecycleEventArgs $eventArgs): void
    {
        $entity = $eventArgs->getObject();
        if ($entity instanceof EntitySanitizerInterface) {
            $this->sanitize($entity, 'preUpdate');
        }
    }

    private function sanitize(EntitySanitizerInterface $entity, string $eventType): void
    {
        $this->logger->info("[$eventType] Before sanitization", [
            'class' => $entity::class,
            'description' => $entity->getDescription(),
        ]);

        $entity->sanitizeDescription($this->htmlSanitizer);

        $this->logger->info("[$eventType] After sanitization", [
            'class' => $entity::class,
            'description' => $entity->getDescription(),
        ]);
    }
}
