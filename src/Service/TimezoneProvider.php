<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

readonly class TimezoneProvider
{
    public const string TIMEZONE_EUROPE_PARIS = 'Europe/Paris';

    public function __construct(private Security $security)
    {
    }

    public function getTimezone(): string
    {
        /** @var User $user */
        $user = $this->security->getUser();
        if ($user && $user->getTerritory()) {
            return $user->getTerritory()->getTimezone();
        }

        return self::TIMEZONE_EUROPE_PARIS;
    }

    /**
     * @throws \Exception
     */
    public function getDateTimezone(): \DateTimeZone
    {
        return new \DateTimeZone($this->getTimezone());
    }
}
