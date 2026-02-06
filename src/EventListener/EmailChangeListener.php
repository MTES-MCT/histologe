<?php

namespace App\EventListener;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\SignalementDraft;
use App\Entity\User;
use App\Manager\EmailDeliveryIssueManager;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;

#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
class EmailChangeListener
{
    private UnitOfWork $uow;
    private array $emailsToRemove = [];
    private bool $running = false;

    public function __construct(
        private EmailDeliveryIssueManager $manager,
    ) {
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $this->uow = $eventArgs->getObjectManager()->getUnitOfWork();

        foreach ($this->uow->getScheduledEntityUpdates() as $entity) {
            $changeSet = $this->uow->getEntityChangeSet($entity);
            foreach ($this->getEmailFields($entity) as $field) {
                if (!isset($changeSet[$field])) {
                    continue;
                }

                $change = $changeSet[$field] ?? null;
                if (
                    !is_array($change)
                    || !array_key_exists(0, $change)
                    || !array_key_exists(1, $change)
                ) {
                    continue;
                }

                [$oldEmail, $newEmail] = $change;

                $this->emailsToRemove[(string) $oldEmail] = $newEmail;
            }
        }
    }

    public function postFlush(): void
    {
        if ($this->running || [] === $this->emailsToRemove) {
            return;
        }

        $this->running = true;
        try {
            $emails = $this->emailsToRemove;
            $this->emailsToRemove = [];

            foreach ($emails as $oldEmail => $newEmail) {
                $this->manager->removeEmailDeliveryIssue($oldEmail, $newEmail);
            }
        } finally {
            $this->running = false;
        }
    }

    private function getEmailFields(object $entity): array
    {
        return match (true) {
            $entity instanceof User => ['email'],
            $entity instanceof Partner => ['email'],
            $entity instanceof SignalementDraft => ['emailDeclarant'],
            $entity instanceof Signalement => ['mailProprio', 'mailDeclarant', 'mailOccupant', 'mailAgence'],
            default => [],
        };
    }
}
