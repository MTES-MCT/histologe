<?php

namespace App\Tests\Functional\EventSubscriber;

use App\EventSubscriber\InterventionAbortedSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InterventionAbortedSubscriberTest extends KernelTestCase
{
    public function testEventSubscription(): void
    {
        $this->assertArrayHasKey(InterventionAbortedSubscriber::NAME, InterventionAbortedSubscriber::getSubscribedEvents());
    }
}
