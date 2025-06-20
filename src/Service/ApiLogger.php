<?php

namespace App\Service;

use App\Entity\User;
use App\EventSubscriber\ApiRequestIdSubscriber;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiLogger
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly Security $security,
    ) {
    }

    public function logApiCall(Request $request, Response $response): void
    {
        $requestId = $request->attributes->get(ApiRequestIdSubscriber::REQUEST_API_ID_KEY);
        $startTime = $request->attributes->get(ApiRequestIdSubscriber::REQUEST_START_TIME);
        $executionTime = round(microtime(true) - $startTime, 2);
        $method = $request->getMethod();
        $pathInfo = $request->getPathInfo();
        /** @var User|null $user */
        $user = $this->security->getUser();
        $userId = $user?->getId();

        $data = [
            'request_id' => $requestId,
            'execution_time' => $executionTime,
            'user_id' => $userId,
            'method' => $method,
            'path_info' => $pathInfo,
            'query' => $request->query->all(),
            'request' => $this->sanitizeSensitiveData(json_decode($request->getContent(), true)),
            'files' => $this->extractUploadedFiles($request),
            'response' => $this->sanitizeSensitiveData(json_decode($response->getContent(), true)),
            'response_status' => $response->getStatusCode(),
        ];

        $this->logger->info('API Request '.$method.' '.$pathInfo.' ('.$requestId.')', $data);
    }

    /**
     * @return array<int, array<string, string|int|null>>
     */
    private function extractUploadedFiles(Request $request): array
    {
        $uploadedFiles = [];
        foreach ($request->files as $fileInfo) {
            foreach ($fileInfo as $subFile) {
                if ($subFile instanceof UploadedFile) {
                    $uploadedFiles[] = [
                        'original_name' => $subFile->getClientOriginalName(),
                        'mime_type' => $subFile->getClientMimeType(),
                        'size' => $subFile->getSize(),
                    ];
                }
            }
        }

        return $uploadedFiles;
    }

    /**
     * @param array<string, mixed>|null $data
     *
     * @return array<string, mixed>|null
     */
    private function sanitizeSensitiveData(?array $data): ?array
    {
        if (null === $data) {
            return null;
        }
        foreach ($data as $key => $unused) {
            if (in_array($key, ['password', 'token'])) {
                $data[$key] = '['.$key.']';
            }
        }

        return $data;
    }
}
