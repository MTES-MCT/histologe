<?php

namespace App\Tests\Functional\EventSubscriber;

use App\Event\InterventionRescheduledEvent;
use App\EventSubscriber\InterventionRescheduledSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InterventionRescheduledSubscriberTest extends KernelTestCase
{
    public function testEventSubscription(): void
    {
        $this->assertArrayHasKey(InterventionRescheduledEvent::NAME, InterventionRescheduledSubscriber::getSubscribedEvents());
    }
}
