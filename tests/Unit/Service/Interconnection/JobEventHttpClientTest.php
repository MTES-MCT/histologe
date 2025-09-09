<?php

namespace App\Tests\Unit\Service\Interconnection;

use App\Manager\JobEventManager;
use App\Service\Interconnection\JobEventHttpClient;
use App\Service\Interconnection\JobEventMetaData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class JobEventHttpClientTest extends TestCase
{
    public const string API_WIREMOCK_URL = 'http://signal_logement_wiremock:8080';
    public const string API_RANDOM_URL = 'https://example.com';
    public const string HISTOLOGE_LOCAL_URL = 'http://localhost:8080';

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testRequest(): void
    {
        $payload = [
            'treatmentName' => 'Import HISTOLOGE',
            'fieldList' => [
                ['fieldName' => 'name', 'value' => 'John Doe'],
                ['fieldName' => 'PJ_Documents', 'value' => 'document_encoded_base64'],
            ],
        ];
        $options['body'] = $payload;
        $options['extra'] = [
            'job_event_metadata' => new JobEventMetaData(
                service: 'esabora',
                action: 'push_dossier',
                payload: $payload,
                attachmentsCount: 2,
                attachmentsSize: 10
            ),
        ];

        /** @var string $mockResponseBody */
        $mockResponseBody = json_encode([
            'rowList' => [
                [
                    'documentList' => ['aix2.pdf', 'budget2004.pdf'],
                    'keyDataList' => ['30414'],
                    'documentZipContent' => 'base64',
                ]],
        ]);
        $mockResponse = new MockResponse($mockResponseBody, [
            'http_code' => Response::HTTP_OK,
        ]);
        $mockHttpClient = new MockHttpClient($mockResponse);

        /** @var MockObject&JobEventManager $jobEventManagerMock */
        $jobEventManagerMock = $this->createMock(JobEventManager::class);
        $jobEventManagerMock
            ->expects($this->once())
            ->method('createJobEvent');

        /** @var MockObject&LoggerInterface $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->exactly(2))
            ->method('info');

        $jobEventHttpClient = new JobEventHttpClient(
            $mockHttpClient,
            $jobEventManagerMock,
            $loggerMock,
            self::HISTOLOGE_LOCAL_URL,
        );

        $response = $jobEventHttpClient->request('POST', self::API_WIREMOCK_URL, $options);
        $this->assertSame($mockResponseBody, $response->getContent());
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testRequestThrowsExceptionOnMissingMetadata(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $mockHttpClient = new MockHttpClient();

        /** @var MockObject&JobEventManager $jobEventManagerMock */
        $jobEventManagerMock = $this->createMock(JobEventManager::class);
        /** @var MockObject&LoggerInterface $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);

        $jobEventHttpClient = new JobEventHttpClient(
            $mockHttpClient,
            $jobEventManagerMock,
            $loggerMock,
            self::HISTOLOGE_LOCAL_URL,
        );

        $jobEventHttpClient->request('GET', self::API_WIREMOCK_URL);
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testRequestWithInternalServerError(): void
    {
        $mockResponse = new MockResponse('Internal server error', [
            'http_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
        ]
        );
        $mockHttpClient = new MockHttpClient($mockResponse);

        /** @var MockObject&JobEventManager $jobEventManagerMock */
        $jobEventManagerMock = $this->createMock(JobEventManager::class);
        /** @var MockObject&LoggerInterface $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);

        $jobEventHttpClient = new JobEventHttpClient(
            $mockHttpClient,
            $jobEventManagerMock,
            $loggerMock,
            self::HISTOLOGE_LOCAL_URL
        );
        $options['extra']['job_event_metadata'] = new JobEventMetaData('esabora', 'push_dossier');
        $response = $jobEventHttpClient->request('GET', self::API_WIREMOCK_URL, $options);
        $this->assertSame('Internal server error', $response->getContent(throw: false));
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function testRequestThrowsLogicExceptionWhenUrlIsInvalid(): void
    {
        $options['extra'] = [
            'job_event_metadata' => new JobEventMetaData(
                service: 'esabora',
                action: 'push_dossier',
                payload: ['body' => ['treatmentName' => 'Import HISTOLOGE']]
            ),
        ];

        /** @var string $mockResponseBody */
        $mockResponseBody = json_encode(['message' => 'hello wiremock']);
        $mockResponse = new MockResponse($mockResponseBody, [
            'http_code' => Response::HTTP_OK,
        ]);
        $mockHttpClient = new MockHttpClient($mockResponse);
        /** @var MockObject&JobEventManager $jobEventManagerMock */
        $jobEventManagerMock = $this->createMock(JobEventManager::class);
        /** @var MockObject&LoggerInterface $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);

        $jobEventHttpClient = new JobEventHttpClient(
            $mockHttpClient,
            $jobEventManagerMock,
            $loggerMock,
            self::HISTOLOGE_LOCAL_URL,
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('url must contain "signal_logement_wiremock" when on localhost.');

        $jobEventHttpClient->request('POST', self::API_RANDOM_URL, $options);
    }
}
