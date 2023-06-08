<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Enum\InterventionType;
use App\Entity\Enum\PartnerType;
use App\Entity\Intervention;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;

class InterventionTest extends TestCase
{
    use FixturesHelper;

    public function testInstantiate(): void
    {
        $affectation = $this->getSignalementAffectation(PartnerType::ARS);
        $visiteScheduledAt = new \DateTimeImmutable('2020-05-01 14:30');
        $intervention = (new Intervention())
            ->setSignalement($affectation->getSignalement())
            ->setPartner($affectation->getPartner())
            ->setDetails('Tout est OK')
            ->setType(InterventionType::VISITE)
            ->setScheduledAt($visiteScheduledAt)
            ->setStatus(Intervention::STATUS_PLANNED)
            ->setAdditionalInformation($this->getAdditionalInformationArrete());

        $this->assertEquals(Intervention::STATUS_PLANNED, $intervention->getStatus());
        $this->assertEquals($affectation->getSignalement(), $intervention->getSignalement());
        $this->assertEquals($affectation->getPartner(), $intervention->getPartner());
        $this->assertEquals('Tout est OK', $intervention->getDetails());
        $this->assertEquals(InterventionType::VISITE, $intervention->getType());
        $this->assertEquals($visiteScheduledAt, $intervention->getScheduledAt());
        $this->assertArrayHasKey('arrete_numero', $intervention->getAdditionalInformation());
        $this->assertArrayHasKey('arrete_type', $intervention->getAdditionalInformation());
        $this->assertArrayHasKey('arrete_mainlevee_date', $intervention->getAdditionalInformation());
        $this->assertArrayHasKey('arrete_mainlevee_numero', $intervention->getAdditionalInformation());
    }
}
