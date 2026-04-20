<?php

namespace App\Tests\Unit\Utils;

use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;

trait ClockMockTrait
{
    private MockClock $mockClock;
    private \DateTimeImmutable $now;

    protected function initClock(?string $now = 'now'): void
    {
        // Point de référence FIXE si "now"
        $this->now = 'now' === $now
            ? new \DateTimeImmutable('2024-03-25 12:00:00') // arbitraire, mais fixe pour les tests
            : new \DateTimeImmutable($now);

        $this->mockClock = new MockClock($this->now);

        static::getContainer()->set(ClockInterface::class, $this->mockClock);
    }

    protected function getNow(): \DateTimeImmutable
    {
        return $this->now;
    }

    protected function modifyNow(string $modifier): \DateTimeImmutable
    {
        return $this->now->modify($modifier);
    }

    protected function travel(string $modifier): void
    {
        $this->now = $this->now->modify($modifier);
        $this->mockClock->setNow($this->now);
    }

    protected function setNow(string $datetime): void
    {
        $this->now = new \DateTimeImmutable($datetime);
        $this->mockClock->setNow($this->now);
    }
}
