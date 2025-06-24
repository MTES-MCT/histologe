<?php

namespace App\Controller\Webhook;

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

        // Incrémentation du compteur pour cet événement
        $counter = $this->incrementEventCounter($event);

        // On ne traite que les "blocked" pour l'instant
        if ($event === 'blocked') {
            $message = sprintf(
                '[BREVO][%s] %d occurrence(s)\nEmail: %s\nSubject: %s\nDate: %s\nMessage-ID: %s',
                $event,
                $counter,
                $data['email'] ?? '-',
                $data['subject'] ?? '-',
                $data['date'] ?? '-',
                $data['message-id'] ?? '-'
            );
            \Sentry\captureMessage($message);
        }

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

    // Incrémente et retourne le compteur pour un type d'événement
    private function incrementEventCounter(string $event): int
    {
        if (!isset(self::$eventCounters[$event])) {
            self::$eventCounters[$event] = 1;
        } else {
            self::$eventCounters[$event]++;
        }
        return self::$eventCounters[$event];
    }
} 