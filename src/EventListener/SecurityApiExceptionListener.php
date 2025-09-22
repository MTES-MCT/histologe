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
    public const string ACCESS_DENIED_PARTNER = 'access_denied_partner';
    public const string ACCESS_DENIED_SPECIFIC_PARTNER = 'access_denied_specific_partner';
    public const string ACCESS_DENIED_PARTNER_NOT_FOUND = 'access_denied_partner_not_found';
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
                self::ACCESS_DENIED_PARTNER => 'Vous n\'avez accès à aucun partenaire ou vous devez préciser un partenaireUuid.',
                self::ACCESS_DENIED_SPECIFIC_PARTNER => 'Vous n\'avez pas les permissions pour accéder à ce partenaire.',
                self::ACCESS_DENIED_PARTNER_NOT_FOUND => 'Le partenaire n\'existe pas.',
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
                || self::ACCESS_DENIED_PARTNER === $exception->getMessage()
                || self::ACCESS_DENIED_SPECIFIC_PARTNER === $exception->getMessage()
                || self::ACCESS_DENIED_PARTNER_NOT_FOUND === $exception->getMessage()
                || self::TRANSITION_STATUT_DENIED === $exception->getMessage()
            );
    }
}
