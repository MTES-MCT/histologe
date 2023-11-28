<?php

namespace App\Service\Oilhi;

use App\Messenger\Message\Oilhi\DossierMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HookZapierService
{
    public const ZAPIER_HOOK_URL = 'https://hooks.zapier.com/hooks/catch';
    public const TYPE_SERVICE = 'oilhi';
    public const ACTION_PUSH_DOSSIER = 'push_dossier';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private NormalizerInterface $normalizer,
        #[Autowire(env: 'ZAPIER_OILHI_TOKEN')]
        private string $token,
        #[Autowire(env: 'ZAPIER_OILHI_USER_ID')]
        private string $userId,
        #[Autowire(env: 'ZAPIER_OILHI_CREATE_AIRTABLE_RECORD_ZAP_ID')]
        private string $zapId,
    ) {
    }

    public function pushDossier(DossierMessage $dossierMessage): ResponseInterface|JsonResponse
    {
        $payload = $this->normalizer->normalize($dossierMessage);
        $payload['token'] = $this->token;
        try {
            return $this->httpClient->request(
                'POST',
                self::ZAPIER_HOOK_URL.'/'.$this->userId.'/'.$this->zapId,
                $payload
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
}
