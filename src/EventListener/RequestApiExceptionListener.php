<?php

namespace App\EventListener;

use App\Dto\Api\Request\RequestInterface;
use App\Exception\Suivi\UsagerNotificationRequiredException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class RequestApiExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        /** @var ValidationFailedException|UsagerNotificationRequiredException $previous */
        $previous = $exception->getPrevious() ?? $exception;

        if (!$this->supports($previous)) {
            return;
        }

        if ($previous instanceof ExceptionInterface) {
            /** @var ConstraintViolationListInterface|null $violations */
            $violations = $previous->getViolations(); // @phpstan-ignore-line
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = [
                    'property' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                    'invalidValue' => $violation->getInvalidValue(),
                ];
            }
            $response = [
                'message' => 'Valeurs invalides pour les champs suivants :',
                'status' => Response::HTTP_BAD_REQUEST,
                'errors' => $errors,
            ];
        } else {
            $response = [
                'message' => $previous->getMessage(),
                'status' => Response::HTTP_BAD_REQUEST,
                'errors' => $previous->getErrors(),
            ];
        }

        $event->setResponse(new JsonResponse($response, Response::HTTP_BAD_REQUEST));
    }

    private function supports(?\Throwable $exception = null): bool
    {
        return ($exception instanceof ValidationFailedException
                || $exception instanceof UsagerNotificationRequiredException
        ) && $exception->getValue() instanceof RequestInterface;
    }
}
