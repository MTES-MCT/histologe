<?php

namespace App\Exception\Signalement;

class PrecisionNotFound extends \Exception
{
    public function __construct(string $slugCritere, int $idDraft)
    {
        parent::__construct(sprintf(
            'SignalementDraft n° %s - #%s - Le desordreTraitementProcessor a été trouvé, mais aucune précision ne correspond',
            $idDraft,
            $slugCritere,
        ));
    }
}
