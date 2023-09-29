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
            'type_logement_pieces_a_vivre_piece_1_superficie' => 30,
            'type_logement_pieces_a_vivre_piece_2_superficie' => 15,
            'type_logement_pieces_a_vivre_piece_1_hauteur' => 'oui',
            'type_logement_pieces_a_vivre_piece_2_hauteur' => 'non',
        ];

        $signalementDraftRequest = (new SignalementDraftRequest())
            ->setProfil('locataire')
            ->setTypeLogementPiecesAVivrePieceSuperficie([30, 15])
            ->setTypeLogementPiecesAVivrePieceHauteur(['oui', 'non']);

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
            'type_logement_pieces_a_vivre_piece_superficie' => [30, 15],
            'type_logement_pieces_a_vivre_piece_hauteur' => ['oui', 'non'],
        ];

        $signalementDraft = (new SignalementDraft())->setPayload($payload);
        $result = $serializer->normalize($signalementDraft);

        $this->assertEquals(30, $result['payload']['type_logement_pieces_a_vivre_piece_1_superficie']);
        $this->assertEquals(15, $result['payload']['type_logement_pieces_a_vivre_piece_2_superficie']);
        $this->assertEquals('oui', $result['payload']['type_logement_pieces_a_vivre_piece_1_hauteur']);
        $this->assertEquals('non', $result['payload']['type_logement_pieces_a_vivre_piece_2_hauteur']);
    }
}
