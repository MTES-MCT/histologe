<?php

namespace App\EventListener;

use App\Dto\Api\Request\RequestInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class RequestApiExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        /** @var ValidationFailedException $previous */
        $previous = $exception->getPrevious();
        if (!$this->supports($previous)) {
            return;
        }

        /** @var ConstraintViolationListInterface|null $violations */
        $violations = $previous->getViolations();
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

        $event->setResponse(new JsonResponse($response, Response::HTTP_BAD_REQUEST));
    }

    private function supports(?\Throwable $exception = null): bool
    {
        return $exception instanceof ValidationFailedException && $exception->getValue() instanceof RequestInterface;
    }
}
