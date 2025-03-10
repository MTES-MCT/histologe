<?php

namespace App\Exception\Intervention;

class VisitePartnerAlreadyPlannedException extends \Exception
{
    public function __construct(string $partnerName, string $uuid)
    {
        $message = sprintf(
            'Le partenaire %s a déjà une visite en cours. Veuillez terminer la visite (uuid:%s).',
            $partnerName,
            $uuid
        );

        parent::__construct($message);
    }
}
