<?php

namespace App\Tests\Unit\Factory;

use App\Entity\Enum\QualificationStatus;
use App\Entity\Signalement;
use App\Entity\SignalementQualification;
use App\Factory\SignalementQualificationFactory;
use App\Service\Signalement\Qualification\QualificationStatusService;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SignalementQualificationFactoryTest extends KernelTestCase
{
    public function testCreateSignalementQualificationInstance(): void
    {
        /** @var MockObject&QualificationStatusService $qualificationStatusServiceMock */
        $qualificationStatusServiceMock = $this->createMock(QualificationStatusService::class);
        $qualificationStatusServiceMock
            ->expects($this->atLeast(1))
            ->method('getNDEStatus')
            ->willReturn(QualificationStatus::NDE_AVEREE);

        $signalement = new Signalement();
        $signalement->setDateEntree(new \DateTimeImmutable('2023-01-02'));
        $listNDECriticites = [1];
        $dataConsoSizeYear = '1400';
        $dataConsoYear = '1400';
        $dataConsoSize = '40';
        $dataHasDPE = '1';
        $dataDateDPE = '2023-01-02';
        $signalementQualification = (new SignalementQualificationFactory($qualificationStatusServiceMock))->createNDEInstanceFrom(
            signalement: $signalement,
            listNDECriticites: $listNDECriticites,
            dataConsoSizeYear: $dataConsoSizeYear,
            dataConsoYear: $dataConsoYear,
            dataConsoSize: $dataConsoSize,
            dataHasDPE: $dataHasDPE,
            dataDateDPE: $dataDateDPE
        );

        $this->assertInstanceOf(SignalementQualification::class, $signalementQualification);
        $this->assertEquals(QualificationStatus::NDE_AVEREE, $signalementQualification->getStatus());
    }
}
