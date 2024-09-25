<?php

namespace App\Tests\Unit\Service;

use App\Service\UrlHelper;
use PHPUnit\Framework\TestCase;

class UrlHelperTest extends TestCase
{
    /**
     * @dataProvider provideDataToQueryString
     */
    public function testArrayToQueryString(array $origin, string $result): void
    {
        $this->assertEquals(UrlHelper::arrayToQueryString($origin), $result);
    }

    public function provideDataToQueryString(): \Generator
    {
        yield 'empty' => [[], ''];
        yield 'single' => [['searchTerms' => 'saint médard'], '?searchTerms=saint+m%C3%A9dard'];
        yield 'multiple' => [['searchTerms' => '2024', 'isImported' => 'oui'], '?searchTerms=2024&isImported=oui'];
        yield 'nested' => [['status' => 'en_cours', 'etiquettes' => [1424, 479]], '?status=en_cours&etiquettes%5B%5D=1424&etiquettes%5B%5D=479'];
    }
}
