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

        $this->assertEquals($jobEvent->getId(), null);
        $this->assertEquals($jobEvent->getPartnerId(), 1);
        $this->assertEquals($jobEvent->getSignalementId(), 1);
        $this->assertEquals($jobEvent->getStatus(), JobEvent::STATUS_SUCCESS);
        $this->assertEquals($jobEvent->getType(), 'esabora');
        $this->assertEquals($jobEvent->getTitle(), 'sync_dossier');
        $this->assertEquals($jobEvent->getMessage(), '{"criterionName":"SAS_Référence"}');
        $this->assertEquals($jobEvent->getResponse(), '{"sasReference":"00000000-0000-0000-2022-000000000008"}');
    }
}
