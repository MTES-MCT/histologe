<?php

namespace App\Tests\Unit\Service\Signalement;

use App\Entity\Enum\TypeArrete;
use App\Repository\ArreteRepository;
use App\Service\Signalement\SignalementSameAddressArreteFinder;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SignalementSameAddressArreteFinderTest extends TestCase
{
    use FixturesHelper;

    private MockObject&ArreteRepository $arreteRepository;
    private SignalementSameAddressArreteFinder $arreteFinder;

    protected function setUp(): void
    {
        $this->arreteRepository = $this->createMock(ArreteRepository::class);
        $this->arreteFinder = new SignalementSameAddressArreteFinder($this->arreteRepository);
    }

    public function testFindUsesBanIdWhenAvailable(): void
    {
        $signalement = $this->getSignalement();
        $signalement->setBanIdOccupant('13202_0001');

        $arrete = $this->createArrete(TypeArrete::MISE_EN_SECURITE, new \DateTimeImmutable('2024-01-01'), null);

        $this->arreteRepository->expects($this->once())
            ->method('findByBanId')
            ->with('13202_0001')
            ->willReturn([$arrete]);

        $this->arreteRepository->expects($this->never())
            ->method('findByAddress');

        $this->assertSame([$arrete], $this->arreteFinder->find($signalement));
    }

    public function testFindFallsBackToAddressParsingWhenBanIdHasNoResult(): void
    {
        $signalement = $this->getSignalement();
        $signalement->setBanIdOccupant('13202_0001');
        $signalement->setAdresseOccupant('14 Rue de la Paix');
        $signalement->setCpOccupant('74000');
        $signalement->setInseeOccupant('74010');

        $arrete = $this->createArrete(TypeArrete::MISE_EN_SECURITE, new \DateTimeImmutable('2024-01-01'), null);

        $this->arreteRepository->expects($this->once())
            ->method('findByBanId')
            ->with('13202_0001')
            ->willReturn([]);

        $this->arreteRepository->expects($this->once())
            ->method('findByAddress')
            ->with('14', 'Rue de la Paix', '74000', '74010')
            ->willReturn([$arrete]);

        $this->assertSame([$arrete], $this->arreteFinder->find($signalement));
    }

    public function testFindReturnsEmptyArrayWhenAddressIsIncomplete(): void
    {
        $signalement = $this->getSignalement();
        $signalement->setBanIdOccupant(null);
        $signalement->setInseeOccupant(null);

        $this->arreteRepository->expects($this->never())
            ->method('findByBanId');

        $this->arreteRepository->expects($this->never())
            ->method('findByAddress');

        $this->assertSame([], $this->arreteFinder->find($signalement));
    }

    public function testFindFallsBackToAddressParsingWhenBanIdIsMissing(): void
    {
        $signalement = $this->getSignalement();
        $signalement->setBanIdOccupant(null);
        $signalement->setAdresseOccupant('17 bis Rue de la Paix');
        $signalement->setCpOccupant('74000');
        $signalement->setInseeOccupant('74010');

        $arrete = $this->createArrete(TypeArrete::MISE_EN_SECURITE, new \DateTimeImmutable('2024-01-01'), null);

        $this->arreteRepository->expects($this->never())
            ->method('findByBanId');

        $this->arreteRepository->expects($this->once())
            ->method('findByAddress')
            ->with(
                ['17bis', '17 bis'],
                'Rue de la Paix',
                '74000',
                '74010'
            )
            ->willReturn([$arrete]);

        $this->assertSame([$arrete], $this->arreteFinder->find($signalement));
    }
}
