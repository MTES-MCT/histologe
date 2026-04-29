<?php

namespace App\Tests\Unit\Dto\ServiceSecours;

use App\Dto\ServiceSecours\FormServiceSecoursStep5;
use PHPUnit\Framework\TestCase;

class FormServiceSecoursStep5Test extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideHasDesordreAutreCases')]
    public function testHasDesordreAutre(array $slugs, bool $expected): void
    {
        $dto = new FormServiceSecoursStep5();
        $dto->desordres = $slugs;

        $this->assertSame($expected, $dto->hasDesordreAutre());
    }

    public static function provideHasDesordreAutreCases(): array
    {
        return [
            'avec autre' => [[FormServiceSecoursStep5::DESORDRE_AUTRE_SLUG], true],
            'sans autre' => [['foo', 'bar'], false],
            'vide' => [[], false],
        ];
    }
}
