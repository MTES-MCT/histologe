<?php

namespace App\Controller\Webhook;

use Sentry\Severity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BrevoWebhookController extends AbstractController
{
    // Plages d'IP autorisées pour Brevo
    private const ALLOWED_IPS = [
        '1.179.112.0/20',
        '172.18.0.1/32' // temporaire pour localhost
    ];

    // Compteur d'événements par type (stocké en mémoire, à remplacer par Redis/Memcached si besoin)
    private static array $eventCounters = [];

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

        if ($event === 'blocked') {
            $titleIssue = '[BREVO] Email bloqué';
            $severity = new Severity(Severity::FATAL);
        }
        if ($event === 'hard_bounce') {
            $titleIssue = '[BREVO] Email en hard_bounce';
            $severity = new Severity(Severity::ERROR);
        }
        \Sentry\captureMessage($titleIssue, $severity);


        return new Response('OK', 200);
    }

    private function isAllowedIp(?string $ip): bool
    {
        if ($ip === null) {
            return false;
        }
        foreach (self::ALLOWED_IPS as $cidr) {
            if ($this->ipInRange($ip, $cidr)) {
                return true;
            }
        }
        return false;
    }

    // Vérifie si une IP est dans une plage CIDR
    private function ipInRange(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);
        return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet);
    }
} 