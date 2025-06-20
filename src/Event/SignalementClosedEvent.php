<?php

namespace App\Event;

use App\Entity\Signalement;
use Symfony\Contracts\EventDispatcher\Event;

class SignalementClosedEvent extends Event
{
    public const string NAME = 'signalement.closed';

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(private readonly Signalement $signalement, private readonly array $params)
    {
    }

    public function getSignalement(): ?Signalement
    {
        return $this->signalement;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParams(): array
    {
        return $this->params;
    }
}
