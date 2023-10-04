<?php

namespace App\Serializer;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Model\TypeCompositionLogement;
use App\Entity\SignalementDraft;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class SignalementDraftRequestNormalizer implements DenormalizerInterface, NormalizerInterface
{
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
            if (preg_match(SignalementDraftRequest::PIECES_SUPERFICIE_KEY_PATTERN, $key, $matches)) {
                $transformedData[SignalementDraftRequest::PIECES_SUPERFICIE_KEY][] = $value;
            } elseif (preg_match(SignalementDraftRequest::PIECES_HAUTEUR_KEY_PATTERN, $key, $matches)) {
                $transformedData[SignalementDraftRequest::PIECES_HAUTEUR_KEY][] = $value;
            } elseif (preg_match(SignalementDraftRequest::PATTERN_PHONE_KEY, $key, $matches)) {
                $phone = [
                    'country_code' => $data[$key.'_countrycode'],
                    'phone_number' => $value,
                ];
                $transformedData[$key] = $phone;
            } else {
                $transformedData[$key] = $value;
            }
        }

        return $this->objectNormalizer->denormalize($transformedData, $type);
    }

    /**
     * @return array|\ArrayObject|bool|float|int|string|null
     *
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function normalize(mixed $object, string $format = null, array $context = []): mixed
    {
        $normalizedPayload = [];
        /** @var SignalementDraft $signalementDraft */
        $signalementDraft = $object;

        foreach ($payload = $signalementDraft->getPayload() as $key => $value) {
            if (\in_array(
                $key,
                [SignalementDraftRequest::PIECES_HAUTEUR_KEY, SignalementDraftRequest::PIECES_SUPERFICIE_KEY]
            )) {
                foreach ($payload[$key] as $index => $valueItem) {
                    $pieceNumber = $index + 1;
                    if (SignalementDraftRequest::PIECES_HAUTEUR_KEY === $key) {
                        $normalizedPayload[sprintf(SignalementDraftRequest::PATTERN_HAUTEUR_KEY, $pieceNumber)] = $valueItem;
                    } else {
                        $normalizedPayload[sprintf(SignalementDraftRequest::PATTERN_SUPERFICIE_KEY, $pieceNumber)] = $valueItem;
                    }
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
        return SignalementDraftRequest::class === $type || TypeCompositionLogement::class;
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof SignalementDraft;
    }
}
