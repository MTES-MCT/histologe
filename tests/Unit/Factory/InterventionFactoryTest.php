<?php

namespace App\Tests\Unit\Factory;

use App\Entity\Enum\InterventionType;
use App\Entity\Enum\PartnerType;
use App\Entity\Intervention;
use App\Factory\InterventionFactory;
use App\Service\Esabora\AbstractEsaboraService;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;

class InterventionFactoryTest extends TestCase
{
    use FixturesHelper;

    public function testCreateInstanceIntervention(): void
    {
        $intervention = (new InterventionFactory())->createInstanceFrom(
            $affectation = $this->getAffectation(PartnerType::ARS),
            InterventionType::ARRETE_PREFECTORAL,
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            Intervention::STATUS_DONE,
            AbstractEsaboraService::TYPE_SERVICE,
            1,
            'ARS 13',
            'Il existe 1 arrêté de n° 2023-DD13-00032 daté du 05/05/2023 dans le dossier de n° 2023/DD13/00001.',
            $this->getAdditionalInformationArrete()
        );
        $this->assertEquals(InterventionType::ARRETE_PREFECTORAL, $intervention->getType());
        $this->assertEquals('esabora', $intervention->getProviderName());
        $this->assertEquals(1, $intervention->getProviderId());
        $this->assertEquals('ARS 13', $intervention->getDoneBy());
        $this->assertStringStartsWith('Il existe 1', $intervention->getDetails());
        $this->assertCount(4, $intervention->getAdditionalInformation());
        $this->assertNotNull($affectation->getSignalement());
        $this->assertNotNull($affectation->getPartner());
    }
}
