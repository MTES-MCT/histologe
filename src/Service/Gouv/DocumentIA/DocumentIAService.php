<?php

namespace App\Service\Gouv\DocumentIA;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DocumentIAService
{
    private const URI_WORKFLOW_LIST = 'api/v2/workflows/'; // Liste des workflows disponibles par API
    // private const URI_WORKFLOW_EXECUTE = 'api/v2/workflows/%s/execute'; // Pas implémenté, mais à privilégier en production. A voir plus tard.
    private const URI_WORKFLOW_EXECUTE_SYNC = 'api/v2/workflows/%s/execute-sync'; // Exécution de workflow en mode synchrone
    private const EXECUTE_SYNC_TIMEOUT = 120; // L'exécution synchrone attend la fin du workflow (OCR + LLM)
    private const URI_EXTRACTION_SCHEMAS = 'api/v1/extraction-schemas'; // Liste des types de documents gérés pour l'extraction
    private const WORKFLOW_DOCUMENT_EXTRACTION = 'document-extraction-v2'; // Seul workflow utilisant le paramètre document_type
    private const WORKFLOWS_CACHE_KEY = 'document-ia-workflows';
    private const DOCUMENT_TYPES_CACHE_KEY = 'document-ia-document-types';
    private const CACHE_TTL = 300;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly CacheInterface $cache,
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
     * GET /workflows/, mise en cache.
     * En cas d'erreur, rien n'est mis en cache : l'appel est retenté à la prochaine demande.
     *
     * @return ?array<mixed>
     */
    public function listWorkflows(): ?array
    {
        if ($this->documentIAEnable) {
            try {
                return $this->cache->get(self::WORKFLOWS_CACHE_KEY, function (ItemInterface $item, bool &$save): ?array {
                    $item->expiresAfter(self::CACHE_TTL);

                    $url = $this->documentIAApiUrl.self::URI_WORKFLOW_LIST;
                    $response = $this->httpClient->request('GET', $url, [
                        'headers' => $this->getHeaders(),
                    ]);

                    if (Response::HTTP_OK === $response->getStatusCode()) {
                        $result = $response->toArray();
                        if ('success' !== ($result['status'] ?? null)) {
                            $save = false;
                            $this->logger->warning(\sprintf(
                                'Document IA workflow list failed: %s',
                                $result['message'] ?? 'unknown error'
                            ));
                        }

                        return $result['data'] ?? [];
                    }

                    $save = false;
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

                    return null;
                });
            } catch (\Throwable $exception) {
                $this->logger->error($exception->getMessage());
            }
        }

        return null;
    }

    /**
     * Récupération des types de documents gérés pour l'extraction via l'API, mis en cache
     * GET /extraction-schemas.
     * En cas d'erreur, rien n'est mis en cache : l'appel est retenté à la prochaine demande.
     *
     * @return array<string>
     */
    public function listDocumentTypes(): array
    {
        if (!$this->documentIAEnable) {
            return [];
        }

        try {
            return $this->cache->get(self::DOCUMENT_TYPES_CACHE_KEY, function (ItemInterface $item): array {
                $item->expiresAfter(self::CACHE_TTL);

                $response = $this->httpClient->request('GET', $this->documentIAApiUrl.self::URI_EXTRACTION_SCHEMAS, [
                    'headers' => $this->getHeaders(),
                ]);

                return array_column($response->toArray(), 'document_type');
            });
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage());

            return [];
        }
    }

    /**
     * Exécution synchrone d'un workflow sur un document via l'API
     * POST /workflows/{workflowId}/execute-sync (multipart/form-data).
     *
     * Le type de document n'est transmis que pour le workflow qui l'utilise.
     *
     * Le statut de la réponse peut être SUCCESS (data.result), FAILED (data.error_message) ou STARTED.
     *
     * @return ?array<mixed>
     *
     * @throws \Throwable si l'API répond avec un statut d'erreur (voir getProblemDetail()) ou en cas d'erreur de transport
     */
    public function executeWorkflow(string $workflowId, UploadedFile $file, ?string $documentType = null): ?array
    {
        if ($this->documentIAEnable) {
            $url = $this->documentIAApiUrl.\sprintf(self::URI_WORKFLOW_EXECUTE_SYNC, rawurlencode($workflowId));

            $context = [
                'workflow_id' => $workflowId,
                'filename' => $file->getClientOriginalName(),
            ];
            try {
                $fields = [
                    'file' => DataPart::fromPath(
                        $file->getPathname(),
                        $file->getClientOriginalName(),
                        $file->getMimeType()
                    ),
                ];
                if ($documentType && self::WORKFLOW_DOCUMENT_EXTRACTION === $workflowId) {
                    $fields['override'] = json_encode(
                        ['llm_extract_data' => [['param' => 'document_type', 'value' => $documentType]]],
                        \JSON_THROW_ON_ERROR
                    );
                }
                $formData = new FormDataPart($fields);

                $response = $this->httpClient->request('POST', $url, [
                    'headers' => array_merge(
                        $this->getHeaders(),
                        $formData->getPreparedHeaders()->toArray()
                    ),
                    'body' => $formData->bodyToIterable(),
                    'timeout' => self::EXECUTE_SYNC_TIMEOUT,
                    'max_duration' => self::EXECUTE_SYNC_TIMEOUT,
                ]);

                $result = $response->toArray();
                if ('FAILED' === ($result['status'] ?? null)) {
                    $this->logger->warning(\sprintf(
                        'Document IA workflow execution failed: %s',
                        $result['data']['error_message'] ?? 'unknown error'),
                        $context
                    );
                }

                return $result;
            } catch (\Throwable $exception) {
                if ($exception instanceof HttpExceptionInterface) {
                    $context['url'] = $url;
                    $context['response'] = $exception->getResponse()->getContent(false);
                }
                $this->logger->error($exception->getMessage(), $context);

                throw $exception;
            }
        }

        return null;
    }

    /**
     * Détails d'une erreur renvoyée par l'API au format ProblemDetail (RFC 7807) :
     * title, status, detail, code, trace_id, errors...
     *
     * @return array<mixed> vide si l'erreur ne vient pas de l'API ou si la réponse n'est pas en JSON
     */
    public function getProblemDetail(\Throwable $exception): array
    {
        if (!$exception instanceof HttpExceptionInterface) {
            return [];
        }
        $problemDetail = json_decode($exception->getResponse()->getContent(false), true);

        return \is_array($problemDetail) ? $problemDetail : [];
    }
}
