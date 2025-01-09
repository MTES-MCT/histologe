<?php

namespace App\Security;

use App\Entity\ApiUserToken;
use App\Entity\User;
use App\Repository\UserRepository;
use Random\RandomException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Contracts\Translation\TranslatorInterface;

class JsonLoginAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        if (empty($request->getContent())) {
            return false;
        }

        $payload = json_decode($request->getContent(), true);
        if ($request->isMethod('POST')
            && isset($payload['email'])
            && isset($payload['password'])) {
            return true;
        }

        return false;
    }

    public function authenticate(Request $request): Passport
    {
        $payload = json_decode($request->getContent(), true);
        $email = $payload['email'] ?? null;
        $password = $payload['password'] ?? null;

        if (!$email || !$password) {
            throw new AuthenticationException('E-mail ou mot de passe manquant');
        }

        return new Passport(
            new UserBadge($email, function (string $email) {
                $user = $this->userRepository->findOneBy([
                    'email' => $email,
                    'statut' => User::STATUS_ACTIVE,
                ]);

                if (null === $user || !in_array(User::ROLE_API_USER, $user->getRoles(), true)) {
                    return null;
                }

                return $user;
            }),
            new PasswordCredentials($password)
        );
    }

    /**
     * @throws RandomException
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();
        $apiUserToken = new ApiUserToken();
        $user->addApiUserToken($apiUserToken);
        $user->setLastLoginAt(new \DateTimeImmutable());
        $this->userRepository->save($user, true);

        return new JsonResponse([
            'token' => $apiUserToken->getToken(),
            'expires_at' => $apiUserToken->getExpiresAt()->format(\DATE_ATOM),
        ]);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => $this->translator->trans($exception->getMessageKey(), [], 'security'),
            'message' => $this->translator->trans($exception->getMessage(), [], 'security'),
        ], Response::HTTP_UNAUTHORIZED);
    }
}
