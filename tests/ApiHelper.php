<?php

namespace App\Tests;

use Monolog\Handler\TestHandler;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

trait ApiHelper
{
    public function hasXrequestIdHeaderAndOneApiRequestLog(KernelBrowser $client): void
    {
        $this->responseHasXRequestIdHeader($client);
        $this->hasOneApiRequestLog();
    }

    private function responseHasXRequestIdHeader(KernelBrowser $client): void
    {
        /** @var Response $response */
        $response = $client->getResponse();
        $requestId = $response->headers->get('X-Request-ID');
        $this->assertNotEmpty($requestId);
    }

    private function hasOneApiRequestLog(): void
    {
        /** @var TestHandler $testHandler */
        $testHandler = static::getContainer()->get('monolog.handler.main');
        $this->assertInstanceOf(TestHandler::class, $testHandler);

        $records = $testHandler->getRecords();
        $apiLogs = array_filter($records, function ($record) {
            return str_starts_with((string) $record['message'], 'API Request');
        });

        $this->assertCount(1, $apiLogs, 'Il devrait y avoir exactement un log API Request');
    }
}
