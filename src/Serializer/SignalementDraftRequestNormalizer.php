<?php

namespace App\Serializer;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\SignalementDraft;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class SignalementDraftRequestNormalizer implements DenormalizerInterface, NormalizerInterface
{
    private const PIECES_SUPERFICIE_KEY_PATTERN = '/^type_logement_pieces_a_vivre_piece_(\d+)_superficie$/';
    private const PIECES_HAUTEUR_KEY_PATTERN = '/^type_logement_pieces_a_vivre_piece_(\d+)_hauteur$/';
    private const PIECES_SUPERFICIE_KEY = 'type_logement_pieces_a_vivre_piece_superficie';
    private const PIECES_HAUTEUR_KEY = 'type_logement_pieces_a_vivre_piece_hauteur';

    public function __construct(
        /** @var ObjectNormalizer $objectNormalizer */
        #[Autowire(service: 'serializer.normalizer.object')]
        private NormalizerInterface $objectNormalizer
    ) {
    }

    public function denormalize($data, $type, $format = null, array $context = []): mixed
    {
        $transformedData = [];
        foreach ($data as $key => $value) {
            if (preg_match(self::PIECES_SUPERFICIE_KEY_PATTERN, $key, $matches)) {
                $transformedData[self::PIECES_SUPERFICIE_KEY][] = $value;
            } elseif (preg_match(self::PIECES_HAUTEUR_KEY_PATTERN, $key, $matches)) {
                $transformedData[self::PIECES_HAUTEUR_KEY][] = $value;
            } else {
                $transformedData[$key] = $value;
            }
        }

        return $this->objectNormalizer->denormalize($transformedData, SignalementDraftRequest::class);
    }

    /**
     * @return array|\ArrayObject|bool|float|int|string|null
     *
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function normalize(mixed $object, string $format = null, array $context = [])
    {
        $normalizedPayload = [];
        /** @var SignalementDraft $signalementDraft */
        $signalementDraft = $object;

        foreach ($payload = $signalementDraft->getPayload() as $key => $value) {
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

    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return SignalementDraftRequest::class === $type;
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof SignalementDraft;
    }
}
