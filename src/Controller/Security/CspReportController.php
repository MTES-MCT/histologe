<?php

namespace App\Controller\Security;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CspReportController
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/csp-report', name: 'csp_report', methods: ['POST'])]
    public function report(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);

        if (isset($payload['csp-report'])) {
            $report = $payload['csp-report'];

            $logMessage = sprintf(
                'CSP Violation: blocked-uri=%s, violated-directive=%s, document-uri=%s',
                $report['blocked-uri'] ?? 'N/A',
                $report['violated-directive'] ?? 'N/A',
                $report['document-uri'] ?? 'N/A',
            );
            $this->logger->warning($logMessage);
            \Sentry\captureMessage($logMessage);
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
