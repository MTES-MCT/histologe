<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Enum\PartnerType;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;

class AffectationTest extends TestCase
{
    use FixturesHelper;

    /**
     * @dataProvider providePartnerType
     */
    public function testIsSynchronizeWithEsabora(PartnerType $partnerType, bool $isSynchronized): void
    {
        $affectation = $this->getAffectation($partnerType, $isSynchronized);
        $this->assertEquals($isSynchronized, $affectation->isSynchronizeWithEsabora());
    }

    public function providePartnerType(): \Generator
    {
        yield 'ARS success' => [PartnerType::ARS, true];
        yield 'ARS failed' => [PartnerType::ARS, false];
        yield 'SCHS success' => [PartnerType::COMMUNE_SCHS, true];
        yield 'EPCI success' => [PartnerType::EPCI, true];
        yield 'ADIL not eligible' => [PartnerType::ADIL, false];
    }
}
