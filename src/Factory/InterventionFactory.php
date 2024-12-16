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
        ?string $providerName = null,
        ?int $providerId = null,
        ?string $doneBy = null,
        ?string $details = null,
        ?array $additionalInformation = null,
        ?array $concludeProcedures = [],
    ): Intervention {
        $intervention = (new Intervention())
            ->setPartner($affectation->getPartner())
            ->setSignalement($affectation->getSignalement())
            ->setType($type)
            ->setScheduledAt($scheduledAt)
            ->setRegisteredAt($registeredAt)
            ->setStatus($status)
            ->setProviderName($providerName)
            ->setProviderId($providerId)
            ->setDoneBy($doneBy)
            ->setDetails($details)
            ->setAdditionalInformation($additionalInformation);

        if (!empty($concludeProcedures)) {
            $intervention->setConcludeProcedure($concludeProcedures);
        }

        return $intervention;
    }
}
