<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Interconnection\Esabora\Normalizer;

use App\Service\Interconnection\Esabora\Normalizer\ArreteSISHCollectionResponseNormalizer;
use App\Service\Interconnection\Esabora\Response\DossierArreteSISHCollectionResponse;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;

class ArreteSISHCollectionResponseNormalizerTest extends TestCase
{
    use FixturesHelper;

    public function testNormalizeSplitsWhenArreteAndMainLeveeAreInSameItem(): void
    {
        $response = $this->getDossierArreteAndArreteMainLeveeSISHResponse();

        $response = new DossierArreteSISHCollectionResponse($response, 200);
        $normalizer = new ArreteSISHCollectionResponseNormalizer();
        $normalizedResponse = $normalizer->normalize($response);

        $this->assertCount(2, $normalizedResponse->getCollection());

        [$arreteOnly, $mainLevee] = $normalizedResponse->getCollection();

        $this->assertNotNull($arreteOnly->getArreteNumero());
        $this->assertNull($arreteOnly->getArreteMLNumero());
        $this->assertNull($arreteOnly->getArreteMLDate());

        $this->assertNotNull($mainLevee->getArreteMLNumero());
        $this->assertNotNull($mainLevee->getArreteMLDate());
        $this->assertNotNull($mainLevee->getArreteNumero());
    }
}
