<?php

namespace App\Service\Interconnection\Esabora\Handler;

use App\Entity\Affectation;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.intervention_sish_handler')]
interface InterventionSISHHandlerInterface
{
    public function handle(Affectation $affectation, string $uuidSignalement): void;

    public function getServiceName(): string;
}
