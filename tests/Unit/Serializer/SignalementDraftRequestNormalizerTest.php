<?php

namespace App\Tests\Unit\Serializer;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\SignalementDraft;
use App\Serializer\SignalementDraftRequestNormalizer;
use App\Serializer\SignalementDraftRequestSerializer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class SignalementDraftRequestNormalizerTest extends TestCase
{
    public function testDenormalize(): void
    {
        $normalizer = new SignalementDraftRequestNormalizer(new ObjectNormalizer());

        $data = [
            'profil' => 'locataire',
            'vos_coordonnees_occupant_tel' => '611121314',
            'vos_coordonnees_occupant_tel_countrycode' => 'FR:33',
        ];

        $signalementDraftRequest = (new SignalementDraftRequest())
            ->setProfil('locataire')
            ->setVosCoordonneesOccupantTel('+33611121314')

        $result = $normalizer->denormalize($data, SignalementDraftRequest::class);

        $this->assertEquals($signalementDraftRequest, $result);
    }

    public function testNormalize(): void
    {
        $objectNormalizer = new ObjectNormalizer();
        $normalizers = [
            new SignalementDraftRequestNormalizer($objectNormalizer),
            $objectNormalizer,
        ];
        $serializer = new SignalementDraftRequestSerializer($normalizers);

        $payload = [
            'profil' => 'locataire',
            'vos_coordonnees_occupant_tel' => '0611121314',
            'vos_coordonnees_occupant_tel_countrycode' => 'FR:33',
        ];

        $signalementDraft = (new SignalementDraft())->setPayload($payload);
        $result = $serializer->normalize($signalementDraft);

        $this->assertEquals('0611121314', $result['payload']['vos_coordonnees_occupant_tel']);
        $this->assertEquals('FR:33', $result['payload']['vos_coordonnees_occupant_tel_countrycode']);
    }
}
