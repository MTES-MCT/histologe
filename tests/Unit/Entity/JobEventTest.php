<?php

namespace App\Tests\Unit\Entity;

use App\Entity\JobEvent;
use PHPUnit\Framework\TestCase;

class JobEventTest extends TestCase
{
    public function testJobEventSuccessfullySetted(): void
    {
        $jobEvent = (new JobEvent())
            ->setPartnerId(1)
            ->setSignalementId(1)
            ->setType('esabora')
            ->setTitle('sync_dossier')
            ->setMessage('{"criterionName":"SAS_Référence"}')
            ->setResponse('{"sasReference":"00000000-0000-0000-2022-000000000008"}')
            ->setStatus(JobEvent::STATUS_SUCCESS);

        $this->assertNull($jobEvent->getId());
        $this->assertEquals(1, $jobEvent->getPartnerId());
        $this->assertEquals(1, $jobEvent->getSignalementId());
        $this->assertEquals(JobEvent::STATUS_SUCCESS, $jobEvent->getStatus());
        $this->assertEquals('esabora', $jobEvent->getType());
        $this->assertEquals('sync_dossier', $jobEvent->getTitle());
        $this->assertEquals('{"criterionName":"SAS_Référence"}', $jobEvent->getMessage());
        $this->assertEquals('{"sasReference":"00000000-0000-0000-2022-000000000008"}', $jobEvent->getResponse());
    }
}
