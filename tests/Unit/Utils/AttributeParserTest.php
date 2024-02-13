<?php

namespace App\Tests\Unit\Utils;

use App\Dto\Request\Signalement\CoordonneesBailleurRequest;
use App\Entity\Enum\ProfileDeclarant;
use App\Utils\AttributeParser;
use PHPUnit\Framework\TestCase;

class AttributeParserTest extends TestCase
{
    public function testShowLabelAsFacultatif(): void
    {
        $label = AttributeParser::showLabelAsFacultatif(
            CoordonneesBailleurRequest::class,
            'nom',
            ProfileDeclarant::SERVICE_SECOURS
        );

        $this->assertEquals('(facultatif)', $label);
    }

    public function testDoNotShowLabelAsFacultatif(): void
    {
        $label = AttributeParser::showLabelAsFacultatif(
            CoordonneesBailleurRequest::class,
            'nom',
            ProfileDeclarant::LOCATAIRE
        );

        $this->assertEmpty($label);
    }
}
