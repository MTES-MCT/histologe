<?php

namespace App\EventListener;

use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\HttpKernel\Exception\HttpException;

readonly class ExceptionListener
{
    private const array IGNORED_EXCEPTIONS = [
        MethodNotAllowedException::class,
        NotFoundHttpException::class,
        AccessDeniedHttpException::class,
    ];

    public function __construct(
        private NotificationMailerRegistry $notificationMailerRegistry,
        private ParameterBagInterface $params,
        private LoggerInterface $logger,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if ($exception instanceof NotEncodableValueException) {
            $this->logger->error(sprintf('Format json incorrect : %s', $exception->getMessage()));

            $response = new JsonResponse([
                'success' => false,
                'label' => 'Erreur',
                'message' => 'Le format JSON est incorrect',
            ], Response::HTTP_BAD_REQUEST);

            $event->setResponse($response);
        }

        if ($this->shouldNotifyForException($exception)) {

            $extraData = [];
            if ($exception instanceof HttpException) {
                dump('hhh');
                $extraData = $exception->getHeaders();
                dump($extraData);
            }
    
            $message = "Une erreur s'est produite : {$exception->getMessage()}";
    
            if (!empty($extraData)) {
                $message .= "\n\n📅 Date : " . ($extraData['timestamp'] ?? 'N/A');
                $message .= "\n💾 Base : " . ($extraData['database'] ?? 'N/A');
                $message .= "\n🔍 Hôte : " . ($extraData['host'] ?? 'N/A');
                $message .= "\n❗ Erreur : " . ($extraData['error'] ?? 'N/A');
            }

            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_ERROR_SIGNALEMENT,
                    to: $this->params->get('admin_email'),
                    event: $event,
                    message: $message
                )
            );
        }
    }

    private function shouldNotifyForException(\Throwable $exception): bool
    {
        return !in_array($exception::class, self::IGNORED_EXCEPTIONS, true);
    }
}
