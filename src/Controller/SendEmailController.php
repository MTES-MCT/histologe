<?php

namespace App\Controller;

use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class SendEmailController extends AbstractController
{
    #[Route('/send-email', methods: ['POST'])]
    public function handleSendEmail(
        Request $request,
        LoggerInterface $logger,
        NotificationMailerRegistry $notificationMailerRegistry,
    ): JsonResponse {
        $expectedToken = $this->getParameter('send_error_email_token');
        $providedToken = $request->headers->get('Authorization');

        if ($providedToken !== 'Bearer '.$expectedToken) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['title'])) {
            return new JsonResponse(['error' => 'Invalid request'], 400);
        }

        $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
        $database = $data['database'] ?? 'N/A';
        $host = $data['host'] ?? 'N/A';

        if (isset($data['error'])) {
            // Log de l'erreur
            $logger->error("send-error-mail: {$data['title']} {$data['error']} (DB: {$database}, Host: {$host}, Time: {$timestamp})");

            $errorMessages = [];
            $errorMessages[] = '📅 Date : '.$timestamp;
            $errorMessages[] = '💾 Base : '.$database;
            $errorMessages[] = '🔍 Hôte : '.$host;
            $errorMessages[] = '❗ Erreur : '.$data['error'];


            $notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_CRON,
                    to: $this->getParameter('admin_email'),
                    cronLabel: $data['title'],
                    params: [
                        'count_failed' => 1,
                        'message_failed' => "Une erreur s'est produite lors de l'exécution du job.",
                        'error_messages' => $errorMessages,
                    ],
                )
            );
        } else {
            $notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_CRON,
                    to: $this->getParameter('admin_email'),
                    cronLabel: $data['title'],
                    params: [
                        'count_success' => 1,
                        'message_success' => ($data['message'] ?? 'Succès')." (DB: {$database}, Host: {$host}, Time: {$timestamp})",
                    ],
                )
            );
        }

        return new JsonResponse(['message' => 'Mail sent']);
    }
}
