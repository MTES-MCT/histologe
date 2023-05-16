<?php

namespace App\Factory;

use App\Entity\Affectation;
use App\Entity\Enum\InterventionType;
use App\Entity\Intervention;

class InterventionFactory
{
    public function createInstanceFrom(
        Affectation $affectation,
        InterventionType $type,
        \DateTimeImmutable $scheduledAt,
        \DateTimeImmutable $registeredAt,
        string $status,
        ?string $providerId = null,
        ?string $doneBy = null,
        ?string $details = null,
    ): Intervention {
        return (new Intervention())
            ->setPartner($affectation->getPartner())
            ->setSignalement($affectation->getSignalement())
            ->setType($type)
            ->setScheduledAt($scheduledAt)
            ->setRegisteredAt($registeredAt)
            ->setStatus($status)
            ->setProviderId($providerId)
            ->setDoneBy($doneBy)
            ->setDetails($details);
    }
}
