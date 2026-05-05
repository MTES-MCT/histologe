<?php

namespace App\Manager;

use App\Entity\Enum\PartnerType;
use App\Entity\JobEvent;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class JobEventManager extends Manager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        ManagerRegistry $managerRegistry,
        string $entityName = JobEvent::class,
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    public function createJobEvent(
        string $service,
        string $action,
        string $message,
        string $response,
        string $status,
        int $codeStatus,
        ?int $signalementId,
        ?int $partnerId,
        ?PartnerType $partnerType,
        ?int $attachmentsCount = null,
        ?int $attachmentsSize = null,
    ): JobEvent {
        $jobEvent = (new JobEvent())
            ->setSignalementId($signalementId)
            ->setPartnerId($partnerId)
            ->setPartnerType($partnerType)
            ->setService($service)
            ->setAction($action)
            ->setMessage($message)
            ->setResponse($response)
            ->setStatus($status)
            ->setAttachmentsCount($attachmentsCount)
            ->setAttachmentsSize($attachmentsSize)
            ->setCodeStatus($codeStatus);

        $this->entityManager->persist($jobEvent);

        return $jobEvent;
    }
}
