<?php

declare(strict_types=1);

namespace App\Tests\Service\Interconnection\Esabora\Normalizer;

use App\Service\Interconnection\Esabora\Normalizer\ArreteSISHCollectionResponseNormalizer;
use App\Service\Interconnection\Esabora\Response\DossierArreteSISHCollectionResponse;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;

final class ArreteSISHCollectionResponseNormalizerTest extends TestCase
{
    use FixturesHelper;

    public function testNormalizeSplitsWhenArreteAndMainLeveeAreInSameItem(): void
    {
        $response = $this->getDossierArreteAndArreteMainLeveeSISHResponse();

        $response = new DossierArreteSISHCollectionResponse($response, 200);
        $normalizer = new ArreteSISHCollectionResponseNormalizer();
        $normalizedResponse = $normalizer->normalize($response);

        self::assertCount(2, $normalizedResponse->getCollection());

        [$arreteOnly, $mainLevee] = $normalizedResponse->getCollection();

        self::assertNotNull($arreteOnly->getArreteNumero());
        self::assertNull($arreteOnly->getArreteMLNumero());
        self::assertNull($arreteOnly->getArreteMLDate());

        self::assertNotNull($mainLevee->getArreteMLNumero(), );
        self::assertNotNull($mainLevee->getArreteMLDate());
        self::assertNotNull($mainLevee->getArreteNumero());
    }
}
