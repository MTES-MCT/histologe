<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Enum\PartnerType;
use App\Entity\JobEvent;
use PHPUnit\Framework\TestCase;

class JobEventTest extends TestCase
{
    public function testJobEventSuccessfullySetted(): void
    {
        $jobEvent = (new JobEvent())
            ->setPartnerId(1)
            ->setSignalementId(1)
            ->setService('esabora')
            ->setAction('sync_dossier')
            ->setMessage('{"criterionName":"SAS_Référence"}')
            ->setResponse('{"sasReference":"00000000-0000-0000-2022-000000000008"}')
            ->setPartnerType(PartnerType::ARS)
            ->setCodeStatus(200)
            ->setStatus(JobEvent::STATUS_SUCCESS);

        $this->assertNull($jobEvent->getId());
        $this->assertEquals(1, $jobEvent->getPartnerId());
        $this->assertEquals(1, $jobEvent->getSignalementId());
        $this->assertEquals(JobEvent::STATUS_SUCCESS, $jobEvent->getStatus());
        $this->assertEquals('esabora', $jobEvent->getService());
        $this->assertEquals('sync_dossier', $jobEvent->getAction());
        $this->assertEquals('{"criterionName":"SAS_Référence"}', $jobEvent->getMessage());
        $this->assertEquals('{"sasReference":"00000000-0000-0000-2022-000000000008"}', $jobEvent->getResponse());
        $this->assertEquals(200, $jobEvent->getCodeStatus());
        $this->assertEquals(PartnerType::ARS, $jobEvent->getPartnerType());
    }
}
