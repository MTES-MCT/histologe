<?php

namespace App\Security\Authenticator;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Signalement;
use App\Manager\UserManager;
use App\Repository\SignalementRepository;
use App\Security\Provider\SignalementUserProvider;
use App\Security\User\SignalementUser;
use App\Service\Security\TwoFactorCondition;
use App\Validator\EmailFormatValidator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use function Symfony\Component\String\u;

class CodeSuiviLoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const string LOGIN_FO_ROUTE = 'app_login_fo';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly SignalementRepository $signalementRepository,
        private readonly SignalementUserProvider $signalementUserProvider,
        private readonly TwoFactorCondition $twoFactorCondition,
    ) {
    }

    public function supports(Request $request): bool
    {
        return $request->isMethod('POST')
            && $request->attributes->has('code')
            && str_contains($request->getPathInfo(), '/authentification/');
    }

    public function authenticate(Request $request): Passport
    {
        $codeSuivi = $request->attributes->get('code');

        $signalement = $this->signalementRepository->findOneByCodeForPublic($codeSuivi);
        if (!$signalement) {
            throw new CustomUserMessageAuthenticationException('Code de suivi invalide');
        }

        $visitorType = (string) $request->request->get('visitor-type');
        $firstLetterPrenom = (string) $request->request->get('login-first-letter-prenom', '');
        $firstLetterNom = (string) $request->request->get('login-first-letter-nom', '');
        $codePostal = (string) $request->request->get('login-code-postal', '');
        $csrfToken = (string) $request->request->get('_csrf_token');

        if (empty($firstLetterPrenom)) {
            throw new CustomUserMessageAuthenticationException('Veuillez saisir la première lettre du prénom.');
        }

        if (empty($firstLetterNom)) {
            throw new CustomUserMessageAuthenticationException('Veuillez saisir la première lettre du nom.');
        }

        if (empty($codePostal)) {
            throw new CustomUserMessageAuthenticationException('Veuillez saisir le code postal.');
        }

        if (ProfileDeclarant::LOCATAIRE !== $signalement->getProfileDeclarant()
            && ProfileDeclarant::BAILLEUR_OCCUPANT !== $signalement->getProfileDeclarant()
            && empty($visitorType)
        ) {
            throw new CustomUserMessageAuthenticationException('Merci de sélectionner si vous occupez le logement ou si vous avez fait la déclaration.');
        }

        $this->denyAccessIfNotAllowed($signalement, $firstLetterPrenom, $firstLetterNom, $codePostal, $visitorType);
        $this->denyAccessIfNoEmailForTwoFactor($signalement, $visitorType);

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
                    $usagerData['user'],
                    $usagerData['email'] ?? null,
                );
            }),
            [new CsrfTokenBadge('authenticate', $csrfToken)]
        );
    }

    private function denyAccessIfNotAllowed(
        Signalement $signalement,
        string $inputFirstLetterPrenom,
        string $inputFirstLetterNom,
        string $inputCodePostal,
        ?string $visitorType,
    ): void {
        $inputFirstLetterPrenom = u($inputFirstLetterPrenom)->ascii()->upper()->toString();
        $inputFirstLetterNom = u($inputFirstLetterNom)->ascii()->upper()->toString();

        $testOccupant = false;
        if (ProfileDeclarant::LOCATAIRE === $signalement->getProfileDeclarant()
            || ProfileDeclarant::BAILLEUR_OCCUPANT === $signalement->getProfileDeclarant()
            || UserManager::OCCUPANT === $visitorType
        ) {
            if (!empty($signalement->getPrenomOccupant()) && !empty($signalement->getNomOccupant())) {
                $firstLetterPrenomToCheck = u(mb_substr($signalement->getPrenomOccupant(), 0, 1))->ascii()->upper()->toString();
                $firstLetterNomToCheck = u(mb_substr($signalement->getNomOccupant(), 0, 1))->ascii()->upper()->toString();
                $testOccupant = $firstLetterPrenomToCheck === $inputFirstLetterPrenom && $firstLetterNomToCheck === $inputFirstLetterNom;
            }
        }
        $testDeclarant = false;
        if (!$testOccupant && UserManager::DECLARANT === $visitorType) {
            if (!empty($signalement->getPrenomDeclarant()) && !empty($signalement->getNomDeclarant())) {
                $firstLetterPrenomToCheck = u(mb_substr($signalement->getPrenomDeclarant(), 0, 1))->ascii()->upper()->toString();
                $firstLetterNomToCheck = u(mb_substr($signalement->getNomDeclarant(), 0, 1))->ascii()->upper()->toString();
                $testDeclarant = $firstLetterPrenomToCheck === $inputFirstLetterPrenom && $firstLetterNomToCheck === $inputFirstLetterNom;
            }
        }
        if ((!$testDeclarant && !$testOccupant) || $signalement->getAddress()->getPostCode() !== $inputCodePostal) {
            throw new CustomUserMessageAuthenticationException('Informations incorrectes');
        }
    }

    /**
     * La 2FA usager envoie le code par e-mail : sans adresse, l'usager ne peut pas se connecter.
     */
    private function denyAccessIfNoEmailForTwoFactor(Signalement $signalement, string $visitorType): void
    {
        if (!$this->twoFactorCondition->isEnabledForUsagers()) {
            return;
        }

        if (UserManager::DECLARANT === $visitorType) {
            $email = $signalement->getMailDeclarant();
            $contact = 'l\'occupant du logement';
        } else {
            $email = $signalement->getMailOccupant();
            $contact = 'la personne qui a créé votre dossier';
        }

        if (empty($email) || EmailFormatValidator::EMAIL_HISTOLOGE_INCONNU === $email) {
            throw new CustomUserMessageAuthenticationException(\sprintf('Merci de demander à %s de renseigner une adresse e-mail valide pour vous.', $contact));
        }

        if (EmailFormatValidator::isInvalidEmail($email)) {
            throw new CustomUserMessageAuthenticationException(\sprintf('L\'adresse e-mail associée à votre dossier n\'est pas valide. Merci de demander à %s de la corriger.', $contact));
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }
        $codeSuivi = $request->attributes->get('code');

        return new RedirectResponse($this->urlGenerator->generate('front_suivi_signalement', ['code' => $codeSuivi]));
    }

    /**
     * Les routes de la 2FA usager (/2fa-suivi) ne portent pas de code de suivi :
     * sans 2FA en cours (session expirée, retour arrière…), on renvoie vers l'accueil.
     * TODO : on va finir par avoir un formulaire login usager unique, on pourra rediriger dessus
     */
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        if (empty($request->attributes->get('code'))) {
            if ($request->hasSession()) {
                /** @var FlashBagAwareSessionInterface $session */
                $session = $request->getSession();
                $session->getFlashBag()->add('error', [
                    'title' => 'Session expirée',
                    'message' => 'Votre session a expiré. Merci d\'utiliser à nouveau le lien de suivi de votre dossier pour vous connecter.',
                ]);
            }

            return new RedirectResponse($this->urlGenerator->generate('home'));
        }

        return parent::start($request, $authException);
    }

    protected function getLoginUrl(Request $request): string
    {
        $codeSuivi = $request->attributes->get('code');

        return $this->urlGenerator->generate(self::LOGIN_FO_ROUTE, ['code' => $codeSuivi]);
    }
}
