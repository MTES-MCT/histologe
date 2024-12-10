<?php

namespace App\Tests\Unit\Service\Signalement;

use App\Service\Signalement\SignalementInputValueMapper;
use PHPUnit\Framework\TestCase;

class SignalementInputValueMapperTest extends TestCase
{
    /**
     * @dataProvider provideInputValue
     */
    public function testMap(?string $inputValue, bool|string|null $mappedInputValue): void
    {
        $this->assertEquals($mappedInputValue, SignalementInputValueMapper::map($inputValue));
    }

    public function provideInputValue(): \Generator
    {
        yield 'Input with Oui value' => ['oui', true];
        yield 'Input with Non value' => ['non', false];
        yield 'Input with Nsp value' => ['nsp', null];
        yield 'Input with caf value' => ['caf', 'CAF'];
        yield 'Input with msa value' => ['msa', 'MSA'];
        yield 'Input with null value' => [null, null];
        yield 'Input with empty value' => ['', null];
    }
}
