<?php

namespace App\Manager;

use App\Entity\Enum\PartnerType;
use App\Entity\JobEvent;
use Doctrine\Persistence\ManagerRegistry;

class JobEventManager extends AbstractManager
{
    public function __construct(ManagerRegistry $managerRegistry, string $entityName = JobEvent::class)
    {
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
            ->setCodeStatus($codeStatus);

        $this->save($jobEvent);

        return $jobEvent;
    }
}
