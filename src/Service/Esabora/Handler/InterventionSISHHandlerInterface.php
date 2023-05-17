<?php

namespace App\Service\Esabora\Handler;

use App\Entity\Affectation;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.intervention_sish_handler')]
interface InterventionSISHHandlerInterface
{
    public function handle(Affectation $affectation);

    public function getServiceName(): string;

    public static function getPriority(): int;
}
