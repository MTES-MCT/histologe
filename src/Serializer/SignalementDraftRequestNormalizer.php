<?php

namespace App\Serializer;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\SignalementDraft;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class SignalementDraftRequestNormalizer implements DenormalizerInterface, NormalizerInterface
{
    private const PIECES_SUPERFICIE_KEY_PATTERN = '/^type_logement_pieces_a_vivre_superficie_piece_(\d+)$/';
    private const PIECES_HAUTEUR_KEY_PATTERN = '/^type_logement_pieces_a_vivre_hauteur_piece_(\d+)$/';
    private const PIECES_SUPERFICIE_KEY = 'type_logement_pieces_a_vivre_superficie_piece';
    private const PIECES_HAUTEUR_KEY = 'type_logement_pieces_a_vivre_hauteur_piece';

    public function __construct(private ObjectNormalizer $objectNormalizer)
    {
    }

    public function denormalize($data, $type, $format = null, array $context = [])
    {
        $transformedData = [];
        foreach ($data as $key => $value) {
            if (preg_match(self::PIECES_SUPERFICIE_KEY_PATTERN, $key, $matches)) {
                $transformedData[self::PIECES_SUPERFICIE_KEY][] = $value;
            } else if (preg_match(self::PIECES_HAUTEUR_KEY_PATTERN, $key, $matches)) {
                $transformedData[self::PIECES_HAUTEUR_KEY][] = $value;
            } else {
                $transformedData[$key] = $value;
            }
        }

        return $this->objectNormalizer->denormalize($transformedData, SignalementDraftRequest::class);
    }

    public function normalize(mixed $object, string $format = null, array $context = [])
    {
        $normalizedPayload = [];
        /** @var SignalementDraft $signalementDraft */
        $signalementDraft = $object;

        if (empty($payload = $signalementDraft->getPayload())) {
            return $object;
        }

        foreach ($payload as $key => $value) {
            if (\in_array(
                $key,
                [self::PIECES_HAUTEUR_KEY, self::PIECES_SUPERFICIE_KEY]
            )) {
                foreach ($payload[$key] as $index => $valueItem) {
                    $pieceNumber = $index + 1;
                    $normalizedPayload[$key.'_'.$pieceNumber] = $valueItem;
                }
            } else {
                $normalizedPayload[$key] = $value;
            }
        }
        $signalementDraft->setPayload($normalizedPayload);

        return $this->objectNormalizer->normalize($signalementDraft, $format, $context);
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return SignalementDraftRequest::class === $type;
    }

    public function supportsNormalization(mixed $data, string $format = null)
    {
        return $data instanceof SignalementDraft;
    }
}
