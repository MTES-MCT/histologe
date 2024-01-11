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
            if (preg_match(SignalementDraftRequest::PATTERN_PHONE_KEY, $key, $matches)) {
                if (!$value) {
                    continue;
                }
                if (!isset($data[$key.'_countrycode'])) {
                    $data[$key.'_countrycode'] = 'FR:33';
                }
                $indicatif = $data[$key.'_countrycode'];
                if (str_contains($data[$key.'_countrycode'], ':')) {
                    $indicatif = explode(':', $data[$key.'_countrycode'])[1];
                }
                $phone = '+'.$indicatif.$value;
                $transformedData[$key] = $phone;
            } elseif (preg_match(SignalementDraftRequest::PATTERN_FILE_UPLOAD, $key, $matches)) {
                $transformedData[SignalementDraftRequest::FILE_UPLOAD_KEY][$key] = $data[$key];
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

        foreach ($signalementDraft->getPayload() as $key => $value) {
            $normalizedPayload[$key] = $value;
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

    public function getSupportedTypes(?string $format): array
    {
        return [
            SignalementDraftRequest::class => true, 
            TypeCompositionLogement::class => true
        ];
    }
}
