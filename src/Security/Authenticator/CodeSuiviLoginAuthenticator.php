<?php

namespace App\Security\Authenticator;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Security\Provider\SignalementUserProvider;
use App\Security\User\SignalementUser;
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
    public const string LOGIN_ROUTE_SUIVI_MESSAGES = 'front_suivi_signalement_messages';
    public const string LOGIN_ROUTE_PROCEDURE = 'front_suivi_procedure';
    public const string LOGIN_ROUTE_EXPORT_PDF = 'show_export_pdf_usager';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly SignalementRepository $signalementRepository,
        private readonly SignalementUserProvider $signalementUserProvider,
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

        $signalement = $this->signalementRepository->findOneByCodeForPublic($codeSuivi, false);
        if (!$signalement) {
            throw new CustomUserMessageAuthenticationException('Code de suivi invalide');
        }

        $visitorType = $request->request->get('visitor-type');
        $firstLetterPrenom = mb_strtoupper($request->request->get('login-first-letter-prenom'));
        $firstLetterNom = mb_strtoupper($request->request->get('login-first-letter-nom'));
        $codePostal = $request->request->get('login-code-postal');

        if (ProfileDeclarant::LOCATAIRE !== $signalement->getProfileDeclarant()
            && ProfileDeclarant::BAILLEUR_OCCUPANT !== $signalement->getProfileDeclarant()
            && empty($visitorType)
        ) {
            throw new CustomUserMessageAuthenticationException('Merci de sélectionner si vous occupez le logement ou si vous avez fait la déclaration.');
        }

        $this->denyAccessIfNotAllowed($signalement, $firstLetterPrenom, $firstLetterNom, $codePostal, $visitorType);

        $userIdentifier = $codeSuivi.':'.$visitorType;

        return new SelfValidatingPassport(
            new UserBadge($userIdentifier, function () use ($signalement, $userIdentifier) {
                [$codeSuivi, $visitorType] = explode(':', $userIdentifier);
                $usagerData = $this->signalementUserProvider->getUsagerData(
                    $signalement,
                    $visitorType,
                    $codeSuivi
                );

                return new SignalementUser(
                    $usagerData['identifier'],
                    $usagerData['email'],
                    $usagerData['user'] ?? null,
                );
            }),
            [new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token'))]
        );
    }

    private function denyAccessIfNotAllowed(
        Signalement $signalement,
        string $inputFirstLetterPrenom,
        string $inputFirstLetterNom,
        string $inputCodePostal,
        ?string $visitorType,
    ): void {
        $testOccupant = false;
        if (ProfileDeclarant::LOCATAIRE === $signalement->getProfileDeclarant()
            || ProfileDeclarant::BAILLEUR_OCCUPANT === $signalement->getProfileDeclarant()
            || 'occupant' === $visitorType
        ) {
            if (!empty($signalement->getPrenomOccupant()) && !empty($signalement->getNomOccupant())) {
                $firstLetterPrenomToCheck = mb_strtoupper(substr($signalement->getPrenomOccupant(), 0, 1));
                $firstLetterNomToCheck = mb_strtoupper(substr($signalement->getNomOccupant(), 0, 1));
                $testOccupant = $firstLetterPrenomToCheck === $inputFirstLetterPrenom && $firstLetterNomToCheck === $inputFirstLetterNom;
            }
        }
        $testDeclarant = false;
        if (!$testOccupant && 'declarant' === $visitorType) {
            if (!empty($signalement->getPrenomDeclarant()) && !empty($signalement->getNomDeclarant())) {
                $firstLetterPrenomToCheck = mb_strtoupper(substr($signalement->getPrenomDeclarant(), 0, 1));
                $firstLetterNomToCheck = mb_strtoupper(substr($signalement->getNomDeclarant(), 0, 1));
                $testDeclarant = $firstLetterPrenomToCheck === $inputFirstLetterPrenom && $firstLetterNomToCheck === $inputFirstLetterNom;
            }
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

        switch ($request->get('_route')) {
            case self::LOGIN_ROUTE_SUIVI:
                return $this->urlGenerator->generate(self::LOGIN_ROUTE_SUIVI, ['code' => $codeSuivi]);
            case self::LOGIN_ROUTE_SUIVI_MESSAGES:
                return $this->urlGenerator->generate(self::LOGIN_ROUTE_SUIVI_MESSAGES, ['code' => $codeSuivi]);
            case self::LOGIN_ROUTE_EXPORT_PDF:
                return $this->urlGenerator->generate(self::LOGIN_ROUTE_EXPORT_PDF, ['code' => $codeSuivi]);
            default:
                return $this->urlGenerator->generate(self::LOGIN_ROUTE_PROCEDURE, ['code' => $codeSuivi]);
        }
    }
}
