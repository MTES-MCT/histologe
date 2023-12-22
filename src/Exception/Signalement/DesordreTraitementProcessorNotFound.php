<?php

namespace App\Exception\Signalement;

class DesordreTraitementProcessorNotFound extends \Exception
{
    public function __construct(string $slugCritere, int $idDraft)
    {
        parent::__construct(sprintf(
            'SignalementDraft n° %s - #%s - Le desordreTraitementProcessor n\a pas été trouvé',
            $idDraft,
            $slugCritere,
        ));
    }
}
