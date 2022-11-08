<?php

namespace App\Tests\Functional\EventSubscriber;

use App\Event\SignalementClosedEvent;
use App\EventSubscriber\SignalementClosedSubscriber;
use PHPUnit\Framework\TestCase;

class SignalementClosedSubscriberTest extends TestCase
{
    public function testEventSubcription(): void
    {
        $this->assertArrayHasKey(SignalementClosedEvent::NAME, SignalementClosedSubscriber::getSubscribedEvents());
    }
}
