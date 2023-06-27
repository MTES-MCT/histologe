<?php

namespace App\Tests\Functional\EventSubscriber;

use App\EventSubscriber\InterventionConfirmedSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InterventionConfirmedSubscriberTest extends KernelTestCase
{
    public function testEventSubscription(): void
    {
        $this->assertArrayHasKey(InterventionConfirmedSubscriber::NAME, InterventionConfirmedSubscriber::getSubscribedEvents());
    }
}
