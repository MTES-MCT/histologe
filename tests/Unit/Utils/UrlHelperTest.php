<?php

namespace App\Tests\Unit\Utils;

use App\Utils\UrlHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class UrlHelperTest extends TestCase
{
    /**
     * @param array<array<mixed>> $origin
     */
    #[DataProvider('provideDataToQueryString')]
    public function testArrayToQueryString(array $origin, string $result): void
    {
        $this->assertEquals(UrlHelper::arrayToQueryString($origin), $result);
    }

    public static function provideDataToQueryString(): \Generator
    {
        yield 'empty' => [[], ''];
        yield 'single' => [['searchTerms' => 'saint médard'], '?searchTerms=saint+m%C3%A9dard'];
        yield 'multiple' => [['searchTerms' => '2024', 'isImported' => 'oui'], '?searchTerms=2024&isImported=oui'];
        yield 'nested' => [['status' => 'en_cours', 'etiquettes' => [1424, 479]], '?status=en_cours&etiquettes%5B%5D=1424&etiquettes%5B%5D=479'];
    }

    #[DataProvider('provideDataRootUrl')]
    public function testBuildRootUrl(string $path, ?Request $request, string $expectedUrl): void
    {
        $this->assertSame($expectedUrl, UrlHelper::buildRootUrl($path, $request));
    }

    public static function provideDataRootUrl(): \Generator
    {
        yield 'Null request returns path' => [
            '/signalement',
            null,
            '/signalement',
        ];

        $requestStandard = Request::create('http://service-secours.localhost/test');
        yield 'Standard port 80 removes port' => [
            '/signalement?profil=service_secours',
            $requestStandard,
            'http://localhost/signalement?profil=service_secours',
        ];

        $requestPort = Request::create('http://service-secours.localhost:8080/test');
        yield 'Custom port 8080 is kept' => [
            '/signalement?profil=service_secours',
            $requestPort,
            'http://localhost:8080/signalement?profil=service_secours',
        ];

        $requestHttps = Request::create('https://service-secours.histologe.beta.gouv.fr/test');
        yield 'HTTPS port 443' => [
            '/signalement',
            $requestHttps,
            'https://histologe.beta.gouv.fr/signalement',
        ];
    }
}
