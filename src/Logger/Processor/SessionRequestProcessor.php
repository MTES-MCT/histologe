<?php

namespace App\Logger\Processor;

use App\Entity\User;
use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsMonologProcessor]
readonly class SessionRequestProcessor implements ProcessorInterface
{
    public const array SENSITIVE_KEYS = ['password', 'token', '_csrf_token', '_token'];

    public function __construct(
        private RequestStack $requestStack,
        private Security $security,
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return $record;
        }

        try {
            $session = $this->requestStack->getSession();
        } catch (SessionNotFoundException) {
            return $record;
        }

        $sessionId = $session->isStarted() ? substr($session->getId(), 0, 8) : '????????';
        $requestId = uniqid('', true); // à récupérer depuis le header de la requête
        $user = $this->security->getUser();
        $userId = ($user instanceof User) ? $user->getId() : null;
        if ($user instanceof User && $user->getId()) {
            $userId = $user->getId();
        }

        $record->extra = [
            'request_id' => $requestId,
            'session_id' => $sessionId,
            'user_id' => $userId,
            'user_ip' => $request->getClientIp(),
            'http' => [
                'url' => $request->getUri(),
                'method' => $request->getMethod(),
                'user_agent' => $request->headers->get('User-Agent'),
                'referer' => $request->headers->get('Referer'),
                'x_forwarded_for' => $request->headers->get('X-Forwarded-For'),
                'get' => $this->sanitizeArray($request->query->all()),
                'post' => $this->sanitizeArray($request->request->all()),
            ],
        ];

        return $record;
    }

    private function sanitizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, self::SENSITIVE_KEYS, true)) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}
