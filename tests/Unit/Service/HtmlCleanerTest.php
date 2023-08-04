<?php

namespace App\Tests\Unit\Service;

use App\Service\HtmlCleaner;
use PHPUnit\Framework\TestCase;

class HtmlCleanerTest extends TestCase
{
    /**
     * @dataProvider providePartnerType
     */
    public function testTextWithHtml(string $textToClean, string $textCleaned): void
    {
        $this->assertEquals(HtmlCleaner::clean($textToClean), $textCleaned);
    }

    public function providePartnerType(): \Generator
    {
        yield 'Bold' => ['<strong>Fat</strong> Joe', 'Fat Joe'];
        yield 'Accents' => ['&eacute;&egrave;&agrave;&ugrave;', 'éèàù'];
        yield 'Bullets' => ['<ul><li>one</li><li>two</li></ul>', 'onetwo'];
        yield 'Line break' => ['First line<br>Second line', 'First lineSecond line'];
        yield 'Paragrap' => ['<p>First paragraph</p><p>Second paragraph</p>', 'First paragraphSecond paragraph'];
    }
}
