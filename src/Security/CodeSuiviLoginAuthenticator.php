<?php

namespace App\Security;

use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class CodeSuiviLoginAuthenticator extends AbstractLoginFormAuthenticator
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
        $firstLetterPrenom = mb_strtoupper($request->request->get('login-first-letter-prenom'));
        $firstLetterNom = mb_strtoupper($request->request->get('login-first-letter-nom'));
        $codePostal = $request->request->get('login-code-postal');

        $signalement = $this->signalementRepository->findOneByCodeForPublic($codeSuivi, false);
        if (!$signalement) {
            throw new CustomUserMessageAuthenticationException('Code de suivi invalide');
        }

        $this->denyAccessIfNotAllowed($signalement, $firstLetterPrenom, $firstLetterNom, $codePostal);

        return new SelfValidatingPassport(
            new UserBadge($codeSuivi),
            [new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token'))]
        );
    }

    private function denyAccessIfNotAllowed(Signalement $signalement, string $inputFirstLetterPrenom, string $inputFirstLetterNom, string $inputCodePostal): void
    {
        $testDeclarant = false;
        if (!empty($signalement->getPrenomDeclarant()) && !empty($signalement->getNomDeclarant())) {
            $firstLetterPrenomToCheck = mb_strtoupper(substr($signalement->getPrenomDeclarant(), 0, 1));
            $firstLetterNomToCheck = mb_strtoupper(substr($signalement->getNomDeclarant(), 0, 1));
            $testDeclarant = $firstLetterPrenomToCheck === $inputFirstLetterPrenom && $firstLetterNomToCheck === $inputFirstLetterNom;
        }
        $testOccupant = false;
        if (!empty($signalement->getPrenomOccupant()) && !empty($signalement->getNomOccupant())) {
            $firstLetterPrenomToCheck = mb_strtoupper(substr($signalement->getPrenomOccupant(), 0, 1));
            $firstLetterNomToCheck = mb_strtoupper(substr($signalement->getNomOccupant(), 0, 1));
            $testOccupant = $firstLetterPrenomToCheck === $inputFirstLetterPrenom && $firstLetterNomToCheck === $inputFirstLetterNom;
        }
        if ((!$testDeclarant && !$testOccupant) || $signalement->getCpOccupant() !== $inputCodePostal) {
            throw new CustomUserMessageAuthenticationException('Informations incorrectes');
        }
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
