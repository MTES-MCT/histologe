<?php

namespace App\Tests\Unit\Utils;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Tests\FixturesHelper;
use App\Utils\DataPropertyArrayFilter;
use PHPUnit\Framework\TestCase;

class DataPropertyArrayFilterTest extends TestCase
{
    use FixturesHelper;

    /**
     * @dataProvider provideData
     */
    public function testFilterByPrefix(array $prefixes, array $filteredDataExpected): void
    {
        $data = json_decode(
            file_get_contents(__DIR__.'/../../../src/DataFixtures/Files/signalement_draft_payload/locataire.json'),
            true
        );

        $filteredData = DataPropertyArrayFilter::filterByPrefix($data, $prefixes);

        $this->assertEquals($filteredDataExpected, $filteredData);
    }

    public function provideData(): \Generator
    {
        yield 'Données Type composition' => [
            SignalementDraftRequest::PREFIX_PROPERTIES_TYPE_COMPOSITION,
            $this->getLocataireTypeComposition(),
        ];

        yield 'Données Situation Foyer' => [
            SignalementDraftRequest::PREFIX_PROPERTIES_SITUATION_FOYER,
            $this->getLocataireSituationFoyer(),
        ];

        yield 'Données Procedure' => [
            SignalementDraftRequest::PREFIX_PROPERTIES_INFORMATION_PROCEDURE,
            $this->getLocataireInformationProcedure(),
        ];

        yield 'Données Information complémentaire' => [
            SignalementDraftRequest::PREFIX_PROPERTIES_INFORMATION_COMPLEMENTAIRE,
            $this->getLocataireInformationComplementaire(),
        ];
    }
}
