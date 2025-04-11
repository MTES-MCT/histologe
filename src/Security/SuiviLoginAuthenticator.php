<?php

namespace App\Security;

use App\Repository\SignalementRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class SuiviLoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const string LOGIN_ROUTE_SUIVI = 'front_suivi_signalement';
    public const string LOGIN_ROUTE_PROCEDURE = 'front_suivi_procedure';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly SignalementRepository $signalementRepository,
    ) {
    }

    public function supports(Request $request): bool
    {
        if ($request->isMethod('POST')
            && $request->get('login-first-letter-prenom')
            && $request->get('login-first-letter-nom')
            && $request->get('login-code-postal')) {
            return true;
        }

        return false;
    }

    public function authenticate(Request $request): Passport
    {
        $codeSuivi = $request->get('code');
        $firstLetterPrenom = strtoupper($request->request->get('login-first-letter-prenom'));
        $firstLetterNom = strtoupper($request->request->get('login-first-letter-nom'));
        $codePostal = $request->request->get('login-code-postal');

        $signalement = $this->signalementRepository->findOneByCodeForPublic($codeSuivi, false);
        if (!$signalement) {
            throw new CustomUserMessageAuthenticationException('Code de suivi invalide');
        }

        $prenomToCheck = !empty($signalement->getPrenomDeclarant()) ? $signalement->getPrenomDeclarant() : $signalement->getPrenomOccupant();
        $nomToCheck = !empty($signalement->getNomDeclarant()) ? $signalement->getNomDeclarant() : $signalement->getNomOccupant();
        if (strtoupper($prenomToCheck[0]) !== $firstLetterPrenom
                || strtoupper($nomToCheck[0]) !== $firstLetterNom
                || $signalement->getCpOccupant() !== $codePostal) {
            throw new CustomUserMessageAuthenticationException('Informations incorrectes');
        }

        return new SelfValidatingPassport(
            new UserBadge($codeSuivi)
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    protected function getLoginUrl(Request $request): string
    {
        $codeSuivi = $request->attributes->get('code');

        return (self::LOGIN_ROUTE_SUIVI === $request->get('_route')) ?
            $this->urlGenerator->generate(self::LOGIN_ROUTE_SUIVI, ['code' => $codeSuivi]) :
            $this->urlGenerator->generate(self::LOGIN_ROUTE_PROCEDURE, ['code' => $codeSuivi]);
    }
}
