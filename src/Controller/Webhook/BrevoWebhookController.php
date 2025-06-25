<?php

namespace App\Controller\Webhook;

use Sentry\Severity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BrevoWebhookController extends AbstractController
{
    /**
     * @var string[]
     */
    private array $allowedIps;

    public function __construct(
        #[Autowire(env: 'BREVO_ALLOWED_IPS')] string $allowedIps,
    ) {
        $this->allowedIps = array_filter(array_map('trim', explode(',', $allowedIps)));
    }

    #[Route('/webhook/brevo', name: 'webhook_brevo', methods: ['POST'])]
    public function handle(Request $request): Response
    {
        $clientIp = $request->getClientIp();
        if (!$this->isAllowedIp($clientIp)) {
            return new Response('Forbidden', 403);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['event'])) {
            return new Response('Bad Request', 400);
        }
        $event = $data['event'];

        \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($data) {
            $scope->setExtra('brevo_payload', $data);
        });

        if ('blocked' === $event) {
            $titleIssue = '[BREVO] Email bloqué';
            $severity = new Severity(Severity::FATAL);
            \Sentry\captureMessage($titleIssue, $severity);
        }
        if ('hard_bounce' === $event) {
            $titleIssue = '[BREVO] Email en hard_bounce';
            $severity = new Severity(Severity::ERROR);
            \Sentry\captureMessage($titleIssue, $severity);
        }

        return new Response('OK', 200);
    }

    private function isAllowedIp(?string $ip): bool
    {
        if (null === $ip) {
            return false;
        }
        foreach ($this->allowedIps as $cidr) {
            if ($this->ipInRange($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    private function ipInRange(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);
        $mask = (int) $mask;

        return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet);
    }
}
