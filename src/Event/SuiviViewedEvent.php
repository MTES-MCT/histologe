<?php

namespace App\Event;

use App\Entity\Signalement;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

class SuiviViewedEvent extends Event
{
    public const string NAME = 'suivi.viewed';

    public function __construct(private readonly Signalement $signalement, private readonly UserInterface $user)
    {
    }

    public function getSignalement(): Signalement
    {
        return $this->signalement;
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }
}
