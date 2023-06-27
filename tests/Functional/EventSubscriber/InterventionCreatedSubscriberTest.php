<?php

namespace App\Tests\Functional\EventSubscriber;

use App\Event\InterventionCreatedEvent;
use App\EventSubscriber\InterventionCreatedSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InterventionCreatedSubscriberTest extends KernelTestCase
{
    public function testEventSubscription(): void
    {
        $this->assertArrayHasKey(InterventionCreatedEvent::NAME, InterventionCreatedSubscriber::getSubscribedEvents());
    }
}
