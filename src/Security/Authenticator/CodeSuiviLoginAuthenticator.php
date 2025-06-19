<?php

namespace App\Security\Authenticator;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Signalement;
use App\Manager\UserManager;
use App\Repository\SignalementRepository;
use App\Security\Provider\SignalementUserProvider;
use App\Security\User\SignalementUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
use function Symfony\Component\String\u;

class CodeSuiviLoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const string LOGIN_FO_ROUTE = 'app_login_fo';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly SignalementRepository $signalementRepository,
        private readonly SignalementUserProvider $signalementUserProvider,
        private readonly EntityManagerInterface $entityManager,
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

        $signalement = $this->signalementRepository->findOneByCodeForPublic($codeSuivi);
        if (!$signalement) {
            throw new CustomUserMessageAuthenticationException('Code de suivi invalide');
        }

        $visitorType = $request->request->get('visitor-type');
        $firstLetterPrenom = $request->request->get('login-first-letter-prenom');
        $firstLetterNom = $request->request->get('login-first-letter-nom');
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
        if ((!$testDeclarant && !$testOccupant) || $signalement->getCpOccupant() !== $inputCodePostal) {
            throw new CustomUserMessageAuthenticationException('Informations incorrectes');
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var SignalementUser $signalementUser */
        $signalementUser = $token->getUser();
        $user = $signalementUser->getUser();
        if ($user) {
            $user->setLastLoginAt(new \DateTimeImmutable());
            $this->entityManager->flush();
        }
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }
        $codeSuivi = $request->attributes->get('code');

        return new RedirectResponse($this->urlGenerator->generate('front_suivi_signalement', ['code' => $codeSuivi]));
    }

    protected function getLoginUrl(Request $request): string
    {
        $codeSuivi = $request->attributes->get('code');

        return $this->urlGenerator->generate(self::LOGIN_FO_ROUTE, ['code' => $codeSuivi]);
    }
}
