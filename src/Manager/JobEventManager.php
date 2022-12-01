<?php

namespace App\Manager;

use App\Entity\JobEvent;
use Doctrine\Persistence\ManagerRegistry;

class JobEventManager extends AbstractManager
{
    public function __construct(ManagerRegistry $managerRegistry, string $entityName = JobEvent::class)
    {
        $this->managerRegistry = $managerRegistry;
        $this->entityName = $entityName;
    }

    public function createJobEvent(
        string $type,
        string $title,
        string $message,
        string $response,
        string $status,
        ?int $signalementId,
        ?int $partnerId
    ): void {
        $jobEvent = (new JobEvent())
            ->setSignalementId($signalementId)
            ->setPartnerId($partnerId)
            ->setType($type)
            ->setTitle($title)
            ->setMessage($message)
            ->setResponse($response)
            ->setStatus($status);

        $this->save($jobEvent);
    }
}
