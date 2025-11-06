<?php

namespace App\Security\Authenticator;

use App\Entity\Enum\UserStatus;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class FormLoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const string LOGIN_ROUTE = 'app_login';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request->isMethod('POST') && $this->getLoginUrl($request) === $request->getPathInfo();
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');

        if (empty($email)) {
            throw new CustomUserMessageAuthenticationException('Veuillez saisir votre adresse email.');
        }

        if (empty($password)) {
            throw new CustomUserMessageAuthenticationException('Veuillez saisir votre mot de passe.');
        }

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        $passport = new Passport(
            new UserBadge($email, function (string $email) {
                return $this->userRepository->findAgentByEmail(email: $email, userStatus: UserStatus::ACTIVE, acceptRoleApi: false);
            }),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
                new RememberMeBadge(),
            ]
        );
        $request->request->remove('password');

        return $passport;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var User $user */
        $user = $token->getUser();
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }
        $listRouteBOParameters = [];
        if (!$user->isPartnerAdmin() && !$user->isTerritoryAdmin() && !$user->isSuperAdmin()) {
            $listRouteBOParameters = ['mesDossiersMessagesUsagers' => '1', 'mesDossiersAverifier' => '1', 'mesDossiersActiviteRecente' => '1'];
        }

        return new RedirectResponse($this->urlGenerator->generate('back_dashboard', $listRouteBOParameters));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
