<?php

namespace App\Tests\Functional\EventSubscriber;

use App\EventSubscriber\InterventionCanceledSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InterventionCanceledSubscriberTest extends KernelTestCase
{
    public function testEventSubscription(): void
    {
        $this->assertArrayHasKey(InterventionCanceledSubscriber::NAME, InterventionCanceledSubscriber::getSubscribedEvents());
    }
}
