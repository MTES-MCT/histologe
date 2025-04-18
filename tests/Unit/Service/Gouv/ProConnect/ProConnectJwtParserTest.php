<?php

namespace App\Tests\Unit\Service\Gouv\ProConnect;

use App\Service\Gouv\ProConnect\ProConnectJwtParser;
use PHPUnit\Framework\TestCase;

class ProConnectJwtParserTest extends TestCase
{
    public function testParseReturnsExpectedClaims(): void
    {
        // Chemin relatif vers le fichier contenant le JWT mocké
        $jwtFile = __DIR__.'/../../../../../tools/wiremock/src/Resources/ProConnect/userinfo.txt';

        $this->assertFileExists($jwtFile, 'Le fichier userinfo.txt est introuvable.');

        $jwt = file_get_contents($jwtFile);

        $parser = new ProConnectJwtParser();
        $claims = $parser->parse($jwt);

        $this->assertIsArray($claims);
        $this->assertArrayHasKey('email', $claims);
        $this->assertSame('proconnect@signal-logement.fr', $claims['email']);

        $this->assertArrayHasKey('uid', $claims);
        $this->assertSame('7855', $claims['uid']);

        $this->assertArrayHasKey('given_name', $claims);
        $this->assertSame('Proconnect', $claims['given_name']);

        $this->assertArrayHasKey('family_name', $claims);
        $this->assertSame('P', $claims['family_name']);
    }
}
