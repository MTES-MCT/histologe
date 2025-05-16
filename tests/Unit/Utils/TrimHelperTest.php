<?php

namespace App\Tests\Unit\Utils;

use App\Utils\TrimHelper;
use PHPUnit\Framework\TestCase;

class TrimHelperTest extends TestCase
{
    /**
     * @dataProvider provideData
     */
    public function testTrimHelper(mixed $dataIn, mixed $dataOut): void
    {
        $this->assertEquals($dataOut, TrimHelper::safeTrim($dataIn));
    }

    public function provideData(): \Generator
    {
        yield 'null' => [null, null];
        yield 'empty string' => ['', ''];
        yield 'int' => [4, 4];
        yield 'blank string' => ['   ', ''];
        yield 'normal case' => ['  Normalement ', 'Normalement'];
        yield 'espace insecable' => ["\u{00A0}bonjour", 'bonjour'];
    }
}
