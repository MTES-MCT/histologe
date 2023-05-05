<?php

namespace App\Tests\Functional\EventSubscriber;

use App\Event\InterventionEditedEvent;
use App\EventSubscriber\InterventionEditedSubscriber;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InterventionEditedSubscriberTest extends KernelTestCase
{
    public function testEventSubscription(): void
    {
        $this->assertArrayHasKey(InterventionEditedEvent::NAME, InterventionEditedSubscriber::getSubscribedEvents());
    }
}
