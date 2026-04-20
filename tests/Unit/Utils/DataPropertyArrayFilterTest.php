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
     *
     * @param array<string> $prefixes
     * @param array<string> $filteredDataExpected
     */
    public function testFilterByPrefix(array $prefixes, array $filteredDataExpected): void
    {
        $provideData = [];
        $provideData[] = [
            SignalementDraftRequest::PREFIX_PROPERTIES_TYPE_COMPOSITION,
            $this->getLocataireTypeComposition(
                withCompositionLogementNombrePersonnes: true,
                withCompositionLogementSuperficie: true,
                withDateEmmenagement: true
            ),
        ];
        $provideData[] = [
            SignalementDraftRequest::PREFIX_PROPERTIES_SITUATION_FOYER,
            $this->getLocataireSituationFoyer(),
        ];
        $provideData[] = [
            SignalementDraftRequest::PREFIX_PROPERTIES_INFORMATION_PROCEDURE,
            $this->getLocataireInformationProcedure(
                withInfoProcedureBailleurPrevenu: true,
                withInfoProcedureBailDate: true
            ),
        ];
        $provideData[] = [
            SignalementDraftRequest::PREFIX_PROPERTIES_INFORMATION_COMPLEMENTAIRE,
            $this->getLocataireInformationComplementaire(withMontantLoyer: true),
        ];

        foreach ($provideData as $dataItem) {
            [$prefixes, $filteredDataExpected] = $dataItem;
            $data = json_decode(
                (string) file_get_contents(__DIR__.'/../../../src/DataFixtures/Files/signalement_draft_payload/locataire.json'),
                true
            );

            $filteredData = DataPropertyArrayFilter::filterByPrefix($data, $prefixes);

            $this->assertEquals($filteredDataExpected, $filteredData);
        }
    }
}
