<?php

namespace App\Controller\Security;

use Psr\Log\LoggerInterface;
use Sentry\State\Scope;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

readonly class CspReportController
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/csp-report', name: 'csp_report', methods: ['POST'])]
    public function report(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);

        if (isset($payload['csp-report'])) {
            $report = $payload['csp-report'];
            if (
                (isset($report['source-file']) && str_contains($report['source-file'], 'extension'))
                || (isset($report['blocked-uri']) && str_contains($report['blocked-uri'], 'extension'))
            ) {
                return new Response('', Response::HTTP_NO_CONTENT);
            }

            if (isset($report['violated-directive'])) {
                \Sentry\configureScope(function (Scope $scope) use ($report): void {
                    $scope->setTag('violated-directive', $report['violated-directive']);
                });
            }

            if (isset($report['document-uri'])) {
                \Sentry\configureScope(function (Scope $scope) use ($report): void {
                    $scope->setTag('document-uri', $report['document-uri']);
                });
            }

            $logMessage = sprintf(
                'CSP Violation: violated-directive=%s',
                $report['violated-directive'] ?? 'N/A',
            );
            $this->logger->warning($logMessage);
            \Sentry\captureMessage($logMessage);
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
