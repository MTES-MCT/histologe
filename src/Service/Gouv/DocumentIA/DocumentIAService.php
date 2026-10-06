<?php

namespace App\Service\Gouv\DocumentIA;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DocumentIAService
{
    private const URI_WORKFLOW_LIST = 'api/v2/workflows/'; // Liste des workflows disponibles par API
    // private const URI_WORKFLOW_EXECUTE = 'api/v2/workflows/%s/execute'; // Pas implémenté, mais à privilégier en production. A voir plus tard.
    private const URI_WORKFLOW_EXECUTE_SYNC = 'api/v2/workflows/%s/execute-sync'; // Exécution de workflow en mode synchrone
    private const EXECUTE_SYNC_TIMEOUT = 120; // L'exécution synchrone attend la fin du workflow (OCR + LLM)

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        #[Autowire(env: 'DOCUMENT_IA_API_URL')]
        private readonly string $documentIAApiUrl,
        #[Autowire(env: 'DOCUMENT_IA_API_KEY')]
        private readonly string $documentIAApiKey,
        #[Autowire(env: 'DOCUMENT_IA_ENABLE')]
        private readonly string $documentIAEnable,
    ) {
    }

    /**
     * Headers basiques à envoyer à l'API.
     *
     * @return array<string, string>
     */
    private function getHeaders(): array
    {
        return [
            'X-API-KEY' => $this->documentIAApiKey,
        ];
    }

    /**
     * Récupération de la liste des workflows disponibles via l'API
     * GET /workflows/.
     *
     * @return ?array<mixed>
     */
    public function listWorkflows(): ?array
    {
        if ($this->documentIAEnable) {
            $url = $this->documentIAApiUrl.self::URI_WORKFLOW_LIST;
            $headers = $this->getHeaders();

            try {
                $response = $this->httpClient->request('GET', $url, [
                    'headers' => $headers,
                ]);

                if (Response::HTTP_OK === $response->getStatusCode()) {
                    $result = $response->toArray();
                    if ('success' !== ($result['status'] ?? null)) {
                        $this->logger->warning(\sprintf(
                            'Document IA workflow list failed: %s',
                            $result['message'] ?? 'unknown error'
                        ));
                    }

                    return $result['data'] ?? [];
                }
                if (Response::HTTP_UNAUTHORIZED === $response->getStatusCode()
                    || Response::HTTP_FORBIDDEN === $response->getStatusCode()
                ) {
                    $this->logger->error(\sprintf(
                        'Document IA API workflow list failed for: %s (status %s)',
                        $url,
                        $response->getStatusCode())
                    );
                } else {
                    $this->logger->warning(\sprintf(
                        'Document IA API workflow list failed for: %s (status %s)',
                        $url,
                        $response->getStatusCode())
                    );
                }
            } catch (\Throwable $exception) {
                $this->logger->error($exception->getMessage());
            }
        }

        return null;
    }

    /**
     * Exécution synchrone d'un workflow sur un document via l'API
     * POST /workflows/{workflowId}/execute-sync (multipart/form-data).
     *
     * Le statut de la réponse peut être SUCCESS (data.result), FAILED (data.error_message) ou STARTED.
     *
     * @return ?array<mixed>
     *
     * @throws \Throwable si l'API répond avec un statut différent de 200 ou en cas d'erreur de transport
     */
    public function executeWorkflow(string $workflowId, UploadedFile $file): ?array
    {
        if ($this->documentIAEnable) {
            $url = $this->documentIAApiUrl.\sprintf(self::URI_WORKFLOW_EXECUTE_SYNC, rawurlencode($workflowId));

            $context = [
                'workflow_id' => $workflowId,
                'filename' => $file->getClientOriginalName(),
            ];
            try {
                $formData = new FormDataPart([
                    'file' => DataPart::fromPath(
                        $file->getPathname(),
                        $file->getClientOriginalName(),
                        $file->getMimeType()
                    ),
                ]);

                $response = $this->httpClient->request('POST', $url, [
                    'headers' => array_merge(
                        $this->getHeaders(),
                        $formData->getPreparedHeaders()->toArray()
                    ),
                    'body' => $formData->bodyToIterable(),
                    'timeout' => self::EXECUTE_SYNC_TIMEOUT,
                    'max_duration' => self::EXECUTE_SYNC_TIMEOUT,
                ]);

                if (Response::HTTP_OK === $response->getStatusCode()) {
                    $result = $response->toArray();
                    if ('FAILED' === ($result['status'] ?? null)) {
                        $this->logger->warning(\sprintf(
                            'Document IA workflow execution failed: %s',
                            $result['data']['error_message'] ?? 'unknown error'),
                            $context
                        );
                    }

                    return $result;
                }
                $statusCode = $response->getStatusCode();
            } catch (\Throwable $exception) {
                $this->logger->error($exception->getMessage(), $context);

                throw $exception;
            }

            $this->logger->error(\sprintf(
                'Document IA API workflow execution failed for: %s (status %s)',
                $url,
                $statusCode),
                $context
            );

            throw new \RuntimeException(\sprintf('%s (code %d)', Response::$statusTexts[$statusCode] ?? 'Erreur inconnue', $statusCode));
        }

        return null;
    }
}
