<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class SuiviLoginAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const string LOGIN_ROUTE = 'app_login_suivi';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly UserRepository $userRepository,
        private readonly SignalementRepository $signalementRepository
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
        $codeSuivi = $request->get('code_suivi');
        $fromEmail = $request->get('from_email');
        if ($signalement = $this->signalementRepository->findOneByCodeForPublic($codeSuivi, false)) {
    
            $firstLetterPrenom = $request->get('login-first-letter-prenom');
            if (empty($firstLetterPrenom) || \strlen($firstLetterPrenom) !== 1) {
                $this->addFlash('error', 'Merci de corriger la première lettre du prénom.');
            } else {
                $prenomDeclarant = $signalement->getPrenomDeclarant();
                $firstLetterPrenomDeclarant = \strtolower(\substr($prenomDeclarant, 0, 1));
                if ($firstLetterPrenomDeclarant !== \strtolower($firstLetterPrenom)) {
                    $this->addFlash('error', 'La première lettre du prénom ne correspond pas.');
                }
            }

            $firstLetterNom = $request->get('login-first-letter-nom');
            if (empty($firstLetterNom) || \strlen($firstLetterNom) !== 1) {
                $this->addFlash('error', 'Merci de corriger la première lettre du nom de famille.');
            } else {
                $nomDeclarant = $signalement->getNomDeclarant();
                $firstLetterNomDeclarant = \strtolower(\substr($nomDeclarant, 0, 1));
                if ($firstLetterNomDeclarant !== \strtolower($firstLetterNom)) {
                    $this->addFlash('error', 'La première lettre du nom de famille ne correspond pas.');
                }
            }

            $codePostal = $request->get('login-code-postal');
            if (empty($codePostal) || \strlen($codePostal) !== 5) {
                $this->addFlash('error', 'Merci de corriger le code postal du logement.');
            } else {
                $codePostalLogement = $signalement->getCpOccupant();
                if ($codePostal !== $codePostalLogement) {
                    $this->addFlash('error', 'Le code postal ne correspond pas.');
                }
            }
        }



        $passport = new Passport(
            new UserBadge($email, function (string $email) {
                return $this->userRepository->findAgentByEmail(email: $email, userStatus: User::STATUS_ACTIVE, acceptRoleApi: false);
            }),
            new PasswordCredentials($request->request->get('password', '')),
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
        $user->setLastLoginAt(new \DateTimeImmutable());
        $this->userRepository->save(entity: $user, flush: true);
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        $codeSuivi = $request->request->get('code_suivi');
        $fromEmail = $request->request->get('from_email');
        return new RedirectResponse($this->urlGenerator->generate('front_suivi_signalement', ['code' => $codeSuivi, 'fromEmail' => $fromEmail]));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}


/*


    #[Route(
        '/connexion-suivi-signalement',
        name: 'app_login_suivi_signalement',
        defaults: ['show_sitemap' => false]
    )]
    public function loginSuiviSignalement(
        Request $request,
        SignalementRepository $signalementRepository,
    ): Response {
        $codeSuivi = $request->get('code_suivi');
        $fromEmail = $request->get('from_email');
        if ($signalement = $signalementRepository->findOneByCodeForPublic($codeSuivi, false)) {
    
            $firstLetterPrenom = $request->get('login-first-letter-prenom');
            if (empty($firstLetterPrenom) || \strlen($firstLetterPrenom) !== 1) {
                $this->addFlash('error', 'Merci de corriger la première lettre du prénom.');
            } else {
                $prenomDeclarant = $signalement->getPrenomDeclarant();
                $firstLetterPrenomDeclarant = \strtolower(\substr($prenomDeclarant, 0, 1));
                if ($firstLetterPrenomDeclarant !== \strtolower($firstLetterPrenom)) {
                    $this->addFlash('error', 'La première lettre du prénom ne correspond pas.');
                }
            }

            $firstLetterNom = $request->get('login-first-letter-nom');
            if (empty($firstLetterNom) || \strlen($firstLetterNom) !== 1) {
                $this->addFlash('error', 'Merci de corriger la première lettre du nom de famille.');
            } else {
                $nomDeclarant = $signalement->getNomDeclarant();
                $firstLetterNomDeclarant = \strtolower(\substr($nomDeclarant, 0, 1));
                if ($firstLetterNomDeclarant !== \strtolower($firstLetterNom)) {
                    $this->addFlash('error', 'La première lettre du nom de famille ne correspond pas.');
                }
            }

            $codePostal = $request->get('login-code-postal');
            if (empty($codePostal) || \strlen($codePostal) !== 5) {
                $this->addFlash('error', 'Merci de corriger le code postal du logement.');
            } else {
                $codePostalLogement = $signalement->getCpOccupant();
                if ($codePostal !== $codePostalLogement) {
                    $this->addFlash('error', 'Le code postal ne correspond pas.');
                }
            }
        }

        return $this->redirectToRoute('front_suivi_signalement', ['code' => $codeSuivi, 'fromEmail' => $fromEmail]);
    }
*/