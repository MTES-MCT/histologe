<?php

namespace App\Tests\Unit\Service\Gouv\Rial\Request;

use App\Service\Gouv\Rial\Request\RialSearchLocauxParams;
use PHPUnit\Framework\TestCase;

class RialSearchLocauxParamsTest extends TestCase
{
    /**
     * @dataProvider provideDataParsed
     */
    public function testParseBanIdSuccess(
        string $banId,
        string $codeDepartementInsee,
        string $codeCommuneInsee,
        string $codeVoieTopo,
        string $numeroVoirie,
        ?string $indiceRepetitionNumeroVoirie,
    ): void {
        $result = RialSearchLocauxParams::getFromBanId($banId);
        $this->assertEquals($codeDepartementInsee, $result['codeDepartementInsee']);
        $this->assertEquals($codeCommuneInsee, $result['codeCommuneInsee']);
        $this->assertEquals($codeVoieTopo, $result['codeVoieTopo']);
        $this->assertEquals($numeroVoirie, $result['numeroVoirie']);
        $this->assertEquals($indiceRepetitionNumeroVoirie, $result['indiceRepetitionNumeroVoirie']);
    }

    /**
     * @dataProvider provideDataNull
     */
    public function testParseBanIdFail(string $banId): void
    {
        $result = RialSearchLocauxParams::getFromBanId($banId);
        $this->assertNull($result);
    }

    public function provideDataParsed(): \Generator
    {
        yield '63113_1650_0001' => ['63113_1650_0001', '63', '113', '1650', '1', null];
        yield '97120_0460_00067' => ['97120_0460_00067', '971', '20', '0460', '67', null];
        yield '2a004_0820_00002' => ['2a004_0820_00002', '2A', '004', '0820', '2', null];
        yield '2a004_08x20_00002' => ['2a004_08x20_00002', '2A', '004', '08x20', '2', null];
        yield '63113_1650_00050_bis' => ['63113_1650_00050_bis', '63', '113', '1650', '50', 'B'];
        yield '47157_0940_00019_ter' => ['47157_0940_00019_ter', '47', '157', '0940', '19', 'T'];
    }

    public function provideDataNull(): \Generator
    {
        yield '37261' => ['37261'];
        yield '37261_1679' => ['37261_1679'];
        yield 'Et tu dis' => ['Et tu dis'];
    }
}
