<?php

namespace App\Tests\Unit\Utils;

use App\Dto\Request\Signalement\CoordonneesBailleurRequest;
use App\Dto\Request\Signalement\InformationsLogementRequest;
use App\Entity\Enum\ProfileDeclarant;
use App\Utils\AttributeParser;
use PHPUnit\Framework\TestCase;

class AttributeParserTest extends TestCase
{
    /**
     * @dataProvider provideData
     */
    public function testShowLabelAsFacultatif(
        string $dto,
        string $field,
        ProfileDeclarant $profileDeclarant,
        bool $isNewForm,
        string $result,
    ): void {
        $label = AttributeParser::showLabelAsFacultatif($dto, $field, $profileDeclarant, $isNewForm);

        $this->assertEquals($result, $label);
    }

    public function provideData(): \Generator
    {
        yield 'New form - show label as facultatif' => [
            CoordonneesBailleurRequest::class,
            'nom',
            ProfileDeclarant::SERVICE_SECOURS,
            true,
            '(facultatif)',
        ];
        yield 'New form - do not show label as facultatif' => [
            CoordonneesBailleurRequest::class,
            'nom',
            ProfileDeclarant::LOCATAIRE,
            true,
            '',
        ];
        yield 'Old form - show label as facultatif' => [
            InformationsLogementRequest::class,
            'compositionLogementEnfants',
            ProfileDeclarant::LOCATAIRE,
            false,
            '(facultatif)',
        ];
        yield 'Old form - do not show label as facultatif' => [
            InformationsLogementRequest::class,
            'nombrePersonnes',
            ProfileDeclarant::LOCATAIRE,
            false,
            '',
        ];
    }
}
