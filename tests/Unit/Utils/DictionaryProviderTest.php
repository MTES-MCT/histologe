<?php

namespace App\Tests\Unit\Utils;

use App\Utils\DictionaryProvider;
use PHPUnit\Framework\TestCase;

final class DictionaryProviderTest extends TestCase
{
    public function testAllLoadsDictionaryFromAssets(): void
    {
        $projectDir = \dirname(__DIR__, 3);
        $provider = new DictionaryProvider($projectDir);

        $dict = $provider->all();

        $this->assertIsArray($dict);
        $this->assertNotEmpty($dict);
        $this->assertArrayHasKey('oui', $dict);
        $this->assertSame('OUI', $dict['oui']['default'] ?? null);
    }

    public function testTranslateReturnsKnownSlug(): void
    {
        $projectDir = \dirname(__DIR__, 3);
        $provider = new DictionaryProvider($projectDir);

        $this->assertSame('OUI', $provider->translate('oui'));
        $this->assertSame('NON', $provider->translate('non'));
        $this->assertSame('Je ne sais pas', $provider->translate('nsp'));
        $this->assertSame('N\'a pas d\'assurance logement', $provider->translate('pas_assurance_logement'));
    }
}
