<?php

namespace App\Exception\Signalement;

class PrecisionNotFound extends \Exception
{
    public function __construct(string $slugCritere)
    {
        parent::__construct(sprintf(
            '#%s - Le desordreTraitementProcessor a été trouvé, mais aucune précision ne correspond',
            $slugCritere,
        ));
    }
}
