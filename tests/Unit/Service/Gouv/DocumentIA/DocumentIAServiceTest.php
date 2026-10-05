<?php

namespace App\Tests\Unit\Service\Gouv\DocumentIA;

use App\Service\Gouv\DocumentIA\DocumentIAService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DocumentIAServiceTest extends TestCase
{
    private const API_URL = 'https://api.document-ia.test/';
    private const API_KEY = 'secret-api-key';
    private const WORKFLOW_ID = 'document-extraction-v2';

    /** @var MockObject&LoggerInterface */
    private MockObject $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testListWorkflowsSuccess(): void
    {
        $mockHttpClient = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            $this->assertSame('GET', $method);
            $this->assertSame(self::API_URL.'api/v2/workflows/', $url);
            $this->assertContains('X-API-KEY: '.self::API_KEY, $options['headers']);

            return new MockResponse((string) json_encode([
                'status' => 'success',
                'data' => [
                    ['id' => self::WORKFLOW_ID, 'name' => 'Document extraction v2', 'description' => 'Workflow generique'],
                ],
                'message' => 'Available workflows retrieved successfully',
            ]));
        });

        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->never())->method('error');

        $workflows = $this->createService($mockHttpClient)->listWorkflows();

        $this->assertIsArray($workflows);
        $this->assertCount(1, $workflows);
        $this->assertSame(self::WORKFLOW_ID, $workflows[0]['id']);
    }

    public function testListWorkflowsWithNonSuccessStatusLogsWarning(): void
    {
        $mockHttpClient = new MockHttpClient(new MockResponse((string) json_encode([
            'status' => 'error',
            'message' => 'Something went wrong',
        ])));

        $this->logger->expects($this->once())->method('warning')
            ->with('Document IA workflow list failed: Something went wrong');

        $this->assertSame([], $this->createService($mockHttpClient)->listWorkflows());
    }

    public function testListWorkflowsUnauthorizedLogsError(): void
    {
        $mockHttpClient = new MockHttpClient(new MockResponse('{}', ['http_code' => 401]));

        $this->logger->expects($this->once())->method('error');
        $this->logger->expects($this->never())->method('warning');

        $this->assertNull($this->createService($mockHttpClient)->listWorkflows());
    }

    public function testListWorkflowsServerErrorLogsWarning(): void
    {
        $mockHttpClient = new MockHttpClient(new MockResponse('{}', ['http_code' => 500]));

        $this->logger->expects($this->once())->method('warning');
        $this->logger->expects($this->never())->method('error');

        $this->assertNull($this->createService($mockHttpClient)->listWorkflows());
    }

    public function testListWorkflowsDisabledDoesNotCallApi(): void
    {
        $mockHttpClient = new MockHttpClient(function (): MockResponse {
            $this->fail('The API should not be called when Document IA is disabled.');
        });

        $this->assertNull($this->createService($mockHttpClient, '0')->listWorkflows());
    }

    public function testExecuteWorkflowSuccess(): void
    {
        $mockHttpClient = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            $contentType = current(array_filter(
                $options['headers'],
                static fn (string $header) => str_starts_with(strtolower($header), 'content-type:')
            ));

            $this->assertSame('POST', $method);
            $this->assertSame(self::API_URL.'api/v2/workflows/'.self::WORKFLOW_ID.'/execute-sync', $url);
            $this->assertContains('X-API-KEY: '.self::API_KEY, $options['headers']);
            $this->assertStringContainsString('multipart/form-data; boundary=', (string) $contentType);

            $body = '';
            while ('' !== $chunk = ($options['body'])(16384)) {
                $body .= $chunk;
            }
            $this->assertStringContainsString('name="file"; filename="sample.pdf"', $body);
            $this->assertStringContainsString('Content-Type: application/pdf', $body);

            return new MockResponse((string) json_encode([
                'id' => 'exec_123',
                'status' => 'SUCCESS',
                'data' => [
                    'total_processing_time_ms' => 1320,
                    'result' => [
                        'classification' => ['document_type' => 'CNI', 'confidence' => 0.94],
                    ],
                ],
            ]));
        });

        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->never())->method('error');

        $result = $this->createService($mockHttpClient)->executeWorkflow(self::WORKFLOW_ID, $this->getUploadedFile());

        $this->assertIsArray($result);
        $this->assertSame('SUCCESS', $result['status']);
        $this->assertSame('CNI', $result['data']['result']['classification']['document_type']);
    }

    public function testExecuteWorkflowFailedStatusLogsWarning(): void
    {
        $mockHttpClient = new MockHttpClient(new MockResponse((string) json_encode([
            'id' => 'exec_123',
            'status' => 'FAILED',
            'data' => [
                'failed_step' => 'llm_extract_data',
                'error_message' => 'LLM timeout',
            ],
        ])));

        $this->logger->expects($this->once())->method('warning')
            ->with('Document IA workflow execution failed: LLM timeout');

        $result = $this->createService($mockHttpClient)->executeWorkflow(self::WORKFLOW_ID, $this->getUploadedFile());

        $this->assertIsArray($result);
        $this->assertSame('FAILED', $result['status']);
    }

    public function testExecuteWorkflowBadRequestLogsError(): void
    {
        $mockHttpClient = new MockHttpClient(new MockResponse('{}', ['http_code' => 400]));

        $this->logger->expects($this->once())->method('error');
        $this->logger->expects($this->never())->method('warning');

        $this->assertNull(
            $this->createService($mockHttpClient)->executeWorkflow(self::WORKFLOW_ID, $this->getUploadedFile())
        );
    }

    public function testExecuteWorkflowTimeoutLogsWarning(): void
    {
        $mockHttpClient = new MockHttpClient(new MockResponse('{}', ['http_code' => 408]));

        $this->logger->expects($this->once())->method('warning');
        $this->logger->expects($this->never())->method('error');

        $this->assertNull(
            $this->createService($mockHttpClient)->executeWorkflow(self::WORKFLOW_ID, $this->getUploadedFile())
        );
    }

    public function testExecuteWorkflowTransportExceptionLogsError(): void
    {
        $mockHttpClient = new MockHttpClient(static function (): MockResponse {
            throw new TransportException('Connection refused');
        });

        $this->logger->expects($this->once())->method('error')
            ->with('Connection refused', $this->arrayHasKey('workflow_id'));

        $this->assertNull(
            $this->createService($mockHttpClient)->executeWorkflow(self::WORKFLOW_ID, $this->getUploadedFile())
        );
    }

    public function testExecuteWorkflowDisabledDoesNotCallApi(): void
    {
        $mockHttpClient = new MockHttpClient(function (): MockResponse {
            $this->fail('The API should not be called when Document IA is disabled.');
        });

        $this->assertNull(
            $this->createService($mockHttpClient, '0')->executeWorkflow(self::WORKFLOW_ID, $this->getUploadedFile())
        );
    }

    private function createService(MockHttpClient $mockHttpClient, string $enable = '1'): DocumentIAService
    {
        return new DocumentIAService(
            $mockHttpClient,
            $this->logger,
            self::API_URL,
            self::API_KEY,
            $enable,
        );
    }

    private function getUploadedFile(): UploadedFile
    {
        return new UploadedFile(
            __DIR__.'/../../../../files/sample.pdf',
            'sample.pdf',
            'application/pdf',
            null,
            true
        );
    }
}
