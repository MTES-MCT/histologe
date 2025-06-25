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
            return new Response('Forbidden', Response::HTTP_FORBIDDEN);
        }

        $payload = json_decode($request->getContent(), true);
        $event = $payload['event'] ?? null;
        if (!$event) {
            return new Response('Bad Request', Response::HTTP_BAD_REQUEST);
        }

        \Sentry\configureScope(function (\Sentry\State\Scope $scope) use ($payload) {
            $scope->setExtra('brevo_payload', $payload);
        });

        $severity = match ($event) {
            'blocked' => new Severity(Severity::FATAL),
            'hard_bounce' => new Severity(Severity::ERROR),
            default => null,
        };

        if ($severity) {
            \Sentry\captureMessage("[BREVO] Email: {$event}", $severity);
        }

        return new Response('OK', Response::HTTP_OK);
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
