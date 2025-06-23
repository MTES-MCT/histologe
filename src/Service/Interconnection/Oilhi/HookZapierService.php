<?php

namespace App\Service\Interconnection\Oilhi;

use App\Messenger\Message\DossierMessageInterface;
use App\Messenger\Message\Oilhi\DossierMessage;
use App\Service\Interconnection\JobEventMetaData;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HookZapierService
{
    public const string ZAPIER_HOOK_URL = 'https://hooks.zapier.com/hooks/catch';
    public const string TYPE_SERVICE = 'oilhi';
    public const string ACTION_PUSH_DOSSIER = 'push_dossier';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly NormalizerInterface $normalizer,
        #[Autowire(env: 'ZAPIER_OILHI_TOKEN')]
        private readonly string $token,
        #[Autowire(env: 'ZAPIER_OILHI_USER_ID')]
        private readonly string $userId,
        #[Autowire(env: 'ZAPIER_OILHI_CREATE_AIRTABLE_RECORD_ZAP_ID')]
        private readonly string $zapId,
    ) {
    }

    /**
     * @throws ExceptionInterface
     */
    public function pushDossier(DossierMessage $dossierMessage): ResponseInterface|JsonResponse
    {
        $payload = $this->normalizer->normalize($dossierMessage);
        $payload = $this->removeUselessFields($payload);
        $payload['token'] = $this->token;
        $options = [
            'headers' => [
                'Content-Type: application/json',
            ],
            'body' => json_encode($payload),
        ];
        $options['extra']['job_event_metadata'] = $this->getMetaData($dossierMessage, $payload);
        try {
            return $this->httpClient->request(
                'POST',
                self::ZAPIER_HOOK_URL.'/'.$this->userId.'/'.$this->zapId,
                $options,
            );
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }

        $response = [
            'message' => $exception->getMessage(),
            'request' => json_encode($payload),
        ];

        return (new JsonResponse($response))->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE);
    }

    /**
     * Utile pour le traitement interne, mais inutile à l'enregistrement dans Airtable.
     *
     * @param array<mixed> $payload
     *
     * @return array<mixed>
     */
    private function removeUselessFields(array $payload): array
    {
        unset($payload['signalementId']);
        unset($payload['partnerId']);

        return $payload;
    }

    /**
     * @param array<mixed> $payload
     */
    private function getMetaData(DossierMessageInterface $dossierMessage, array $payload): JobEventMetaData
    {
        return new JobEventMetaData(
            service: self::TYPE_SERVICE,
            action: $dossierMessage->getAction(),
            payload: $payload,
            signalementId: $dossierMessage->getSignalementId(),
            partnerId: $dossierMessage->getPartnerId(),
            partnerType: $dossierMessage->getPartnerType(),
        );
    }
}
