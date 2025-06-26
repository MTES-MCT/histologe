<?php

namespace App\EventListener;

use App\Entity\Affectation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SecurityApiExceptionListener
{
    public const string ACCESS_DENIED = 'access_denied';
    public const string TRANSITION_STATUT_DENIED = 'transition_statut_denied';

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if ($this->supports($exception)) {
            $previous = $exception->getPrevious();
            $affectation = null;
            if (method_exists($previous, 'getSubject')) {
                /** @var Affectation $affectation */
                $affectation = $previous->getSubject();
            }

            $message = match ($exception->getMessage()) {
                self::ACCESS_DENIED => 'Vous n\'avez pas l\'autorisation d\'accéder à cette ressource.',
                self::TRANSITION_STATUT_DENIED => sprintf(
                    'Cette transition n\'est pas valide (%s --> %s).',
                    $affectation?->getStatut()->value,
                    $affectation?->getNextStatut()->value
                ),
                default => 'Une erreur inconnue est survenue.',
            };

            $response = [
                'message' => $message,
                'status' => Response::HTTP_FORBIDDEN,
            ];
            $event->setResponse(new JsonResponse($response, Response::HTTP_FORBIDDEN));
        }
    }

    private function supports(?\Throwable $exception = null): bool
    {
        return $exception instanceof AccessDeniedHttpException
            && (
                self::ACCESS_DENIED === $exception->getMessage()
                || self::TRANSITION_STATUT_DENIED === $exception->getMessage()
            );
    }
}
