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
            'type_logement_pieces_a_vivre_superficie_piece_1' => 30,
            'type_logement_pieces_a_vivre_superficie_piece_2' => 15,
            'type_logement_pieces_a_vivre_hauteur_piece_1' => 'oui',
            'type_logement_pieces_a_vivre_hauteur_piece_2' => 'non',
        ];

        $expectedPayload = [
            'type_logement_pieces_a_vivre_superficie_piece' => [30, 15],
            'type_logement_pieces_a_vivre_hauteur_piece' => ['oui', 'non'],
        ];

        $signalementDraftRequest = (new SignalementDraftRequest())
            ->setProfil('locataire')
            ->setTypeLogementPiecesAVivreSuperficiePiece([30, 15])
            ->setTypeLogementPiecesAVivreHauteurPiece(['oui', 'non']);

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
            'type_logement_pieces_a_vivre_superficie_piece' => [30, 15],
            'type_logement_pieces_a_vivre_hauteur_piece' => ['oui', 'non'],
        ];

        $signalementDraft = (new SignalementDraft())->setPayload($payload);
        $result = $serializer->normalize($signalementDraft);

        $this->assertArrayHasKey('type_logement_pieces_a_vivre_superficie_piece_1', $result['payload']);
        $this->assertArrayHasKey('type_logement_pieces_a_vivre_superficie_piece_2', $result['payload']);
        $this->assertArrayHasKey('type_logement_pieces_a_vivre_hauteur_piece_1', $result['payload']);
        $this->assertArrayHasKey('type_logement_pieces_a_vivre_hauteur_piece_2', $result['payload']);
    }
}
