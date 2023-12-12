<?php

namespace App\Exception\Signalement;

class DesordreTraitementProcessorNotFound extends \Exception
{
    public function __construct(string $slugCritere)
    {
        parent::__construct(sprintf(
            '#%s - Le desordreTraitementProcessor n\a pas été trouvé',
            $slugCritere,
        ));
    }
}
