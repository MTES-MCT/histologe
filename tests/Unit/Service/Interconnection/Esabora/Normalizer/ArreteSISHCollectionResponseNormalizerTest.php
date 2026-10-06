<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Interconnection\Esabora\Normalizer;

use App\Service\Interconnection\Esabora\Normalizer\ArreteSISHCollectionResponseNormalizer;
use App\Service\Interconnection\Esabora\Response\DossierArreteSISHCollectionResponse;
use App\Service\Interconnection\Esabora\Response\Model\DossierArreteSISH;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;

class ArreteSISHCollectionResponseNormalizerTest extends TestCase
{
    use FixturesHelper;

    public function testNormalizeDoesNotSplitWhenArreteOnly(): void
    {
        $item = new DossierArreteSISH([
            'keyDataList' => [null, 600],
            'columnDataList' => [
                'Histologe',
                '00000000-0000-0000-2023-000000000010',
                '2023/DD13/0010',
                '14/06/2023',
                '2023/DD13/00664',
                'Arrêté L.511-11 - Suroccupation',
                null,
                null,
                null,
                null,
            ],
        ]);

        $response = $this->createMock(DossierArreteSISHCollectionResponse::class);
        $response->method('getCollection')->willReturn([$item]);

        $normalizer = new ArreteSISHCollectionResponseNormalizer();
        $normalizedResponse = $normalizer->normalize($response);

        $this->assertCount(1, $normalizedResponse->getCollection());
        $result = $normalizedResponse->getCollection()[0];
        $this->assertEquals('2023/DD13/00664', $result->getArreteNumero());
        $this->assertNull($result->getArreteModificatifNumero());
        $this->assertNull($result->getArreteMLNumero());
    }

    public function testNormalizeSplitsWhenArreteAndMainLeveeAreInSameItem(): void
    {
        $item = new DossierArreteSISH([
            'keyDataList' => [null, 600],
            'columnDataList' => [
                'Histologe',
                '00000000-0000-0000-2023-000000000010',
                '2023/DD13/0010',
                '14/06/2023',
                '2023/DD13/00664',
                'Arrêté L.511-11 - Suroccupation',
                '07/08/2023',
                'ML001',
                null,
                null,
            ],
        ]);

        $response = $this->createMock(DossierArreteSISHCollectionResponse::class);
        $response->method('getCollection')->willReturn([$item]);

        $normalizer = new ArreteSISHCollectionResponseNormalizer();
        $normalizedResponse = $normalizer->normalize($response);

        $this->assertCount(2, $normalizedResponse->getCollection());

        [$arreteOnly, $mainLevee] = $normalizedResponse->getCollection();

        // État 1 : Arrêté initial
        $this->assertEquals(600, $arreteOnly->getArreteId());
        $this->assertEquals('2023/DD13/00664', $arreteOnly->getArreteNumero());
        $this->assertEquals('14/06/2023', $arreteOnly->getArreteDate());
        $this->assertNull($arreteOnly->getArreteModificatifNumero());
        $this->assertNull($arreteOnly->getArreteModificatifDate());
        $this->assertNull($arreteOnly->getArreteMLNumero());
        $this->assertNull($arreteOnly->getArreteMLDate());

        // État 2 : Arrêté + mainlevée
        $this->assertEquals(600, $mainLevee->getArreteId());
        $this->assertEquals('2023/DD13/00664', $mainLevee->getArreteNumero());
        $this->assertNull($mainLevee->getArreteModificatifNumero());
        $this->assertEquals('ML001', $mainLevee->getArreteMLNumero());
        $this->assertEquals('07/08/2023', $mainLevee->getArreteMLDate());
    }

    public function testNormalizeSplitsWhenArreteAndModificatifAreInSameItem(): void
    {
        $item = new DossierArreteSISH([
            'keyDataList' => [null, 600],
            'columnDataList' => [
                'Histologe',
                '00000000-0000-0000-2023-000000000010',
                '2023/DD13/0010',
                '14/06/2023',
                '2023/DD13/00664',
                'Arrêté L.511-11 - Suroccupation',
                null,
                null,
                '01/07/2023',
                'AM001',
            ],
        ]);

        $response = $this->createMock(DossierArreteSISHCollectionResponse::class);
        $response->method('getCollection')->willReturn([$item]);

        $normalizer = new ArreteSISHCollectionResponseNormalizer();
        $normalizedResponse = $normalizer->normalize($response);

        $this->assertCount(2, $normalizedResponse->getCollection());

        [$arreteOnly, $arreteModif] = $normalizedResponse->getCollection();

        // État 1 : Arrêté initial
        $this->assertEquals(600, $arreteOnly->getArreteId());
        $this->assertEquals('2023/DD13/00664', $arreteOnly->getArreteNumero());
        $this->assertEquals('14/06/2023', $arreteOnly->getArreteDate());
        $this->assertNull($arreteOnly->getArreteModificatifNumero());
        $this->assertNull($arreteOnly->getArreteModificatifDate());
        $this->assertNull($arreteOnly->getArreteMLNumero());
        $this->assertNull($arreteOnly->getArreteMLDate());

        // État 2 : Arrêté + modificatif
        $this->assertEquals(600, $arreteModif->getArreteId());
        $this->assertEquals('2023/DD13/00664', $arreteModif->getArreteNumero());
        $this->assertEquals('AM001', $arreteModif->getArreteModificatifNumero());
        $this->assertEquals('01/07/2023', $arreteModif->getArreteModificatifDate());
        $this->assertNull($arreteModif->getArreteMLNumero());
        $this->assertNull($arreteModif->getArreteMLDate());
    }

    public function testNormalizeSplitsWhenArreteAndModificatifAndMainLeveeAreInSameItem(): void
    {
        $item = new DossierArreteSISH([
            'keyDataList' => [null, 600],
            'columnDataList' => [
                'Histologe',
                '00000000-0000-0000-2023-000000000010',
                '2023/DD13/0010',
                '14/06/2023',
                '2023/DD13/00664',
                'Arrêté L.511-11 - Suroccupation',
                '07/08/2023',
                'ML001',
                '01/07/2023',
                'AM001',
            ],
        ]);

        $response = $this->createMock(DossierArreteSISHCollectionResponse::class);
        $response->method('getCollection')->willReturn([$item]);

        $normalizer = new ArreteSISHCollectionResponseNormalizer();
        $normalizedResponse = $normalizer->normalize($response);

        $this->assertCount(3, $normalizedResponse->getCollection());

        [$arreteOnly, $arreteModif, $arreteMainLevee] = $normalizedResponse->getCollection();

        // État 1 : Arrêté initial
        $this->assertEquals(600, $arreteOnly->getArreteId());
        $this->assertEquals('2023/DD13/00664', $arreteOnly->getArreteNumero());
        $this->assertEquals('14/06/2023', $arreteOnly->getArreteDate());
        $this->assertNull($arreteOnly->getArreteModificatifNumero());
        $this->assertNull($arreteOnly->getArreteModificatifDate());
        $this->assertNull($arreteOnly->getArreteMLNumero());
        $this->assertNull($arreteOnly->getArreteMLDate());

        // État 2 : Arrêté + modificatif
        $this->assertEquals(600, $arreteModif->getArreteId());
        $this->assertEquals('2023/DD13/00664', $arreteModif->getArreteNumero());
        $this->assertEquals('14/06/2023', $arreteModif->getArreteDate());
        $this->assertEquals('AM001', $arreteModif->getArreteModificatifNumero());
        $this->assertEquals('01/07/2023', $arreteModif->getArreteModificatifDate());
        $this->assertNull($arreteModif->getArreteMLNumero());
        $this->assertNull($arreteModif->getArreteMLDate());

        // État 3 : Arrêté + modificatif + mainlevée
        $this->assertEquals(600, $arreteMainLevee->getArreteId());
        $this->assertEquals('2023/DD13/00664', $arreteMainLevee->getArreteNumero());
        $this->assertEquals('14/06/2023', $arreteMainLevee->getArreteDate());
        $this->assertEquals('AM001', $arreteMainLevee->getArreteModificatifNumero());
        $this->assertEquals('01/07/2023', $arreteMainLevee->getArreteModificatifDate());
        $this->assertEquals('ML001', $arreteMainLevee->getArreteMLNumero());
        $this->assertEquals('07/08/2023', $arreteMainLevee->getArreteMLDate());
    }

    public function testNormalizeDoesNotSplitWhenArreteWithModificatifNumeroWithoutDate(): void
    {
        $item = new DossierArreteSISH([
            'keyDataList' => [null, 600],
            'columnDataList' => [
                'Histologe',
                '00000000-0000-0000-2023-000000000010',
                '2023/DD13/0010',
                '25/01/2026',
                'AP45OL023',
                'Arrêté L.511-11 - Suroccupation',
                null,
                null,
                null,
                'APMOD45K08N',
            ],
        ]);

        $response = $this->createMock(DossierArreteSISHCollectionResponse::class);
        $response->method('getCollection')->willReturn([$item]);

        $normalizer = new ArreteSISHCollectionResponseNormalizer();
        $normalizedResponse = $normalizer->normalize($response);

        $this->assertCount(1, $normalizedResponse->getCollection());
        $result = $normalizedResponse->getCollection()[0];
        $this->assertEquals(600, $result->getArreteId());
        $this->assertEquals('AP45OL023', $result->getArreteNumero());
        $this->assertEquals('25/01/2026', $result->getArreteDate());
        $this->assertNull($result->getArreteModificatifNumero());
        $this->assertNull($result->getArreteModificatifDate());
        $this->assertNull($result->getArreteMLNumero());
        $this->assertNull($result->getArreteMLDate());
    }

    public function testNormalizeSplitsWhenArreteAndMainLeveeWithIncompleteModificatif(): void
    {
        $item = new DossierArreteSISH([
            'keyDataList' => [null, 600],
            'columnDataList' => [
                'Histologe',
                '00000000-0000-0000-2023-000000000010',
                '2023/DD13/0010',
                '25/01/2026',
                'AP45OL023',
                'Arrêté L.511-11 - Suroccupation',
                '27/01/2026',
                'APML45K09O',
                null,
                'APMOD45K08N',
            ],
        ]);

        $response = $this->createMock(DossierArreteSISHCollectionResponse::class);
        $response->method('getCollection')->willReturn([$item]);

        $normalizer = new ArreteSISHCollectionResponseNormalizer();
        $normalizedResponse = $normalizer->normalize($response);

        $this->assertCount(2, $normalizedResponse->getCollection());

        [$arreteOnly, $mainLevee] = $normalizedResponse->getCollection();

        // État 1 : Arrêté initial
        $this->assertEquals(600, $arreteOnly->getArreteId());
        $this->assertEquals('AP45OL023', $arreteOnly->getArreteNumero());
        $this->assertEquals('25/01/2026', $arreteOnly->getArreteDate());
        $this->assertNull($arreteOnly->getArreteModificatifNumero());
        $this->assertNull($arreteOnly->getArreteModificatifDate());
        $this->assertNull($arreteOnly->getArreteMLNumero());
        $this->assertNull($arreteOnly->getArreteMLDate());

        // État 2 : Arrêté + mainlevée (sans modificatif)
        $this->assertEquals(600, $mainLevee->getArreteId());
        $this->assertEquals('AP45OL023', $mainLevee->getArreteNumero());
        $this->assertEquals('25/01/2026', $mainLevee->getArreteDate());
        $this->assertNull($mainLevee->getArreteModificatifNumero());
        $this->assertNull($mainLevee->getArreteModificatifDate());
        $this->assertEquals('APML45K09O', $mainLevee->getArreteMLNumero());
        $this->assertEquals('27/01/2026', $mainLevee->getArreteMLDate());
    }
}
