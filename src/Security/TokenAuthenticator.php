<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\ApiUserTokenRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Contracts\Translation\TranslatorInterface;

class TokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly ApiUserTokenRepository $apiUserTokenRepository,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $authorizationHeader = $request->headers->get('Authorization');

        if (str_starts_with($authorizationHeader, 'Bearer ')) {
            $token = substr($authorizationHeader, 7);

            return new SelfValidatingPassport(new UserBadge($token, function (string $token) {
                $apiUserToken = $this->apiUserTokenRepository->findValidUserToken($token);
                if (null !== $user = $apiUserToken?->getOwnedBy()) {
                    return in_array(User::ROLE_API_USER, $user->getRoles(), true)
                        ? $user
                        : null;
                }

                return null;
            }));
        }

        throw new AuthenticationException('Le token est invalide.');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse([
            'error' => $this->translator->trans($exception->getMessageKey(), $exception->getMessageData(), 'security'),
            'message' => $this->translator->trans($exception->getMessage(), $exception->getMessageData(), 'security'),
        ], Response::HTTP_UNAUTHORIZED);
    }
}
