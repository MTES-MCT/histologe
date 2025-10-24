<?php

namespace App\Event;

use App\Entity\Suivi;
use Symfony\Contracts\EventDispatcher\Event;

class SuiviCreatedEvent extends Event
{
    public const string NAME = 'suivi.created';

    public function __construct(private readonly Suivi $suivi)
    {
    }

    public function getSuivi(): ?Suivi
    {
        return $this->suivi;
    }
}
