<?php

namespace App\Security\Authenticator;

use App\Repository\SignalementRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class LoginBailleurAuthenticator extends AbstractLoginFormAuthenticator
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly SignalementRepository $signalementRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request->isMethod('POST')
            && $request->request->get('bailleur_reference')
            && $request->request->get('bailleur_code')
        ;
    }

    public function authenticate(Request $request): Passport
    {
        $reference = (string) $request->request->get('bailleur_reference');
        $code = (string) $request->request->get('bailleur_code');
        $signalement = $this->signalementRepository->findOneForLoginBailleur($reference, $code);

        if (!$signalement) {
            throw new CustomUserMessageAuthenticationException('La référence et/ou le code ne sont pas valides.');
        }

        return new SelfValidatingPassport(new UserBadge($signalement->getUuid()));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->urlGenerator->generate('front_dossier_bailleur'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $reference = (string) $request->request->get('bailleur_reference', 'inconnu');
        $code = (string) $request->request->get('bailleur_code', 'inconnu');
        $this->logger->warning('Échec d\'authentification bailleur', [
            'reference' => $reference,
            'code' => $code,
        ]);

        return parent::onAuthenticationFailure($request, $exception);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('app_login_bailleur');
    }
}
