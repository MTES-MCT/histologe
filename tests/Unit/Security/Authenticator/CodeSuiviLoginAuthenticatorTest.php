<?php

namespace App\Tests\Unit\Security\Authenticator;

use App\Entity\Address;
use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Security\Authenticator\CodeSuiviLoginAuthenticator;
use App\Security\Provider\SignalementUserProvider;
use App\Security\User\SignalementUser;
use App\Service\Security\TwoFactorCondition;
use App\Tests\FixturesHelper;
use App\Validator\EmailFormatValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Twig\Environment;

class CodeSuiviLoginAuthenticatorTest extends TestCase
{
    use FixturesHelper;

    private Stub&SignalementRepository $signalementRepository;
    private Stub&SignalementUserProvider $signalementUserProvider;
    private Stub&UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        $this->signalementRepository = $this->createStub(SignalementRepository::class);
        $this->signalementUserProvider = $this->createStub(SignalementUserProvider::class);
        $this->urlGenerator = $this->createStub(UrlGeneratorInterface::class);
    }

    /**
     * @param array<string> $requestData
     * @param array<string> $expectedUserData
     */
    #[DataProvider('provideSuccessfulAuthenticationCases')]
    public function testAuthenticateSuccess(
        Signalement $signalement,
        array $requestData,
        array $expectedUserData,
    ): void {
        $request = new Request([], $requestData, ['code' => $requestData['code']]);

        $this->signalementRepository->method('findOneByCodeForPublic')->willReturn($signalement);
        $this->signalementUserProvider->method('getUsagerData')->willReturn([
            'identifier' => $expectedUserData['identifier'],
            'email' => $expectedUserData['email'],
            'user' => new User(),
        ]);

        $authenticator = $this->createAuthenticator();

        $passport = $authenticator->authenticate($request);
        $this->assertInstanceOf(SignalementUser::class, $passport->getUser());
        $this->assertTrue(in_array($expectedUserData['roles'], $passport->getUser()->getRoles()));
    }

    public static function provideSuccessfulAuthenticationCases(): \Generator
    {
        yield 'occupant' => [
            self::createMockSignalement(
                ProfileDeclarant::LOCATAIRE,
                'Martin',
                'Luc',
                '13001',
                '123456789',
                'luc.martin@example.com'
            ),
            [
                'code' => '12345678',
                'visitor-type' => 'occupant',
                'login-first-letter-prenom' => 'L',
                'login-first-letter-nom' => 'M',
                'login-code-postal' => '13001',
                '_csrf_token' => 'token123',
            ],
            [
                'identifier' => '12345678:occupant',
                'email' => 'luc.martin@example.com',
                'roles' => 'ROLE_OCCUPANT',
            ],
        ];

        yield 'declarant' => [
            self::createMockSignalement(
                ProfileDeclarant::TIERS_PARTICULIER,
                'Durand',
                'Nadia',
                '75020',
                '87654321',
                'nadia.durand@example.com'
            ),
            [
                'code' => '87654321',
                'visitor-type' => 'déclarant',
                'login-first-letter-prenom' => 'N',
                'login-first-letter-nom' => 'D',
                'login-code-postal' => '75020',
                '_csrf_token' => 'token456',
            ],
            [
                'identifier' => '87654321:déclarant',
                'email' => 'nadia.durand@example.com',
                'roles' => 'ROLE_DECLARANT',
            ],
        ];
        yield 'occupant accentué minuscule' => [
            self::createMockSignalement(
                ProfileDeclarant::LOCATAIRE,
                'Morin',
                'Édith',
                '30100',
                '123456789',
                'edith.morin@example.com'
            ),
            [
                'code' => '13121312',
                'visitor-type' => 'occupant',
                'login-first-letter-prenom' => 'é',
                'login-first-letter-nom' => 'm',
                'login-code-postal' => '30100',
                '_csrf_token' => 'token789',
            ],
            [
                'identifier' => '13121312:occupant',
                'email' => 'edith.morin@example.com',
                'roles' => 'ROLE_OCCUPANT',
            ],
        ];
        yield 'occupant accentué majuscule' => [
            self::createMockSignalement(
                ProfileDeclarant::LOCATAIRE,
                'Morin',
                'Édith',
                '30100',
                '123456789',
                'edith.morin@example.com'
            ),
            [
                'code' => '13121312',
                'visitor-type' => 'occupant',
                'login-first-letter-prenom' => 'É',
                'login-first-letter-nom' => 'M',
                'login-code-postal' => '30100',
                '_csrf_token' => 'token789',
            ],
            [
                'identifier' => '13121312:occupant',
                'email' => 'edith.morin@example.com',
                'roles' => 'ROLE_OCCUPANT',
            ],
        ];
        yield 'occupant accentué sans accentuation' => [
            self::createMockSignalement(
                ProfileDeclarant::LOCATAIRE,
                'Morin',
                'Édith',
                '30100',
                '123456789',
                'edith.morin@example.com'
            ),
            [
                'code' => '13121312',
                'visitor-type' => 'occupant',
                'login-first-letter-prenom' => 'e',
                'login-first-letter-nom' => 'm',
                'login-code-postal' => '30100',
                '_csrf_token' => 'token789',
            ],
            [
                'identifier' => '13121312:occupant',
                'email' => 'edith.morin@example.com',
                'roles' => 'ROLE_OCCUPANT',
            ],
        ];
    }

    public function testAuthenticateWithInvalidCodeThrowsException(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Code de suivi invalide');

        $request = new Request([], [], ['code' => 'wrongcode']);
        $this->signalementRepository->method('findOneByCodeForPublic')->willReturn(null);
        $authenticator = $this->createAuthenticator();
        $authenticator->authenticate($request);
    }

    public function testAuthenticateWithWrongInitialsThrowsException(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Informations incorrectes');

        $signalement = $this->getSignalementLocataire();
        $request = new Request(
            [],
            [
                'visitor-type' => 'occupant',
                'login-first-letter-prenom' => 'X', // Mauvaise initiale
                'login-first-letter-nom' => 'Y',     // Mauvaise initiale
                'login-code-postal' => '13001',
                '_csrf_token' => 'token123',
            ],
            ['code' => '12345678']
        );

        $this->signalementRepository->method('findOneByCodeForPublic')->willReturn($signalement);

        $authenticator = $this->createAuthenticator();
        $authenticator->authenticate($request);
    }

    public function testAuthenticateWithMissingVisitorTypeThrowsException(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Merci de sélectionner si vous occupez le logement ou si vous avez fait la déclaration.');
        $signalement = $this->getSignalement(profileDeclarant: ProfileDeclarant::TIERS_PRO, nom: 'Martin', prenom: 'Luc', codePostal: '13001', codeSuivi: '12345678');

        $request = new Request(
            [],
            [
                'login-first-letter-prenom' => 'L',
                'login-first-letter-nom' => 'M',
                'login-code-postal' => '13001',
                '_csrf_token' => 'token123',
            ],
            ['code' => '12345678']
        );

        $this->signalementRepository->method('findOneByCodeForPublic')->willReturn($signalement);

        $authenticator = $this->createAuthenticator();
        $authenticator->authenticate($request);
    }

    /**
     * @param array<string> $requestData
     */
    #[DataProvider('provideMissingEmailCases')]
    public function testAuthenticateWithoutValidEmailWhenUsager2faEnabledThrowsException(
        Signalement $signalement,
        array $requestData,
        string $expectedMessage,
    ): void {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage($expectedMessage);

        $request = new Request([], $requestData, ['code' => $signalement->getCodeSuivi()]);
        $this->signalementRepository->method('findOneByCodeForPublic')->willReturn($signalement);

        $this->createAuthenticator(usager2faEnabled: true)->authenticate($request);
    }

    public static function provideMissingEmailCases(): \Generator
    {
        $signalementOccupant = self::createMockSignalement(ProfileDeclarant::TIERS_PARTICULIER, 'Martin', 'Luc', '13001', '12345678', '');
        $signalementOccupant->setMailOccupant(null)->setMailDeclarant('declarant@example.com');
        yield 'occupant sans e-mail' => [
            $signalementOccupant,
            [
                'visitor-type' => 'occupant',
                'login-first-letter-prenom' => 'L',
                'login-first-letter-nom' => 'M',
                'login-code-postal' => '13001',
                '_csrf_token' => 'token123',
            ],
            CodeSuiviLoginAuthenticator::NO_EMAIL_MESSAGE,
        ];

        $signalementDeclarant = self::createMockSignalement(ProfileDeclarant::TIERS_PARTICULIER, 'Durand', 'Nadia', '75020', '87654321', 'occupant@example.com');
        $signalementDeclarant->setMailDeclarant(null);
        yield 'déclarant sans e-mail' => [
            $signalementDeclarant,
            [
                'visitor-type' => 'déclarant',
                'login-first-letter-prenom' => 'N',
                'login-first-letter-nom' => 'D',
                'login-code-postal' => '75020',
                '_csrf_token' => 'token456',
            ],
            CodeSuiviLoginAuthenticator::NO_EMAIL_MESSAGE,
        ];

        $signalementOccupantInconnu = self::createMockSignalement(ProfileDeclarant::LOCATAIRE, 'Martin', 'Luc', '13001', '12345678', EmailFormatValidator::EMAIL_HISTOLOGE_INCONNU);
        yield 'occupant avec e-mail inconnu' => [
            $signalementOccupantInconnu,
            [
                'visitor-type' => 'occupant',
                'login-first-letter-prenom' => 'L',
                'login-first-letter-nom' => 'M',
                'login-code-postal' => '13001',
                '_csrf_token' => 'token123',
            ],
            CodeSuiviLoginAuthenticator::NO_EMAIL_MESSAGE,
        ];

        $signalementOccupantInvalide = self::createMockSignalement(ProfileDeclarant::LOCATAIRE, 'Martin', 'Luc', '13001', '12345678', 'luc.martin@example');
        yield 'occupant avec e-mail invalide' => [
            $signalementOccupantInvalide,
            [
                'visitor-type' => 'occupant',
                'login-first-letter-prenom' => 'L',
                'login-first-letter-nom' => 'M',
                'login-code-postal' => '13001',
                '_csrf_token' => 'token123',
            ],
            CodeSuiviLoginAuthenticator::INVALID_EMAIL_MESSAGE,
        ];

        $signalementDeclarantInvalide = self::createMockSignalement(ProfileDeclarant::TIERS_PARTICULIER, 'Durand', 'Nadia', '75020', '87654321', 'occupant@example.com');
        $signalementDeclarantInvalide->setMailDeclarant('nadia.durand@@example.com');
        yield 'déclarant avec e-mail invalide' => [
            $signalementDeclarantInvalide,
            [
                'visitor-type' => 'déclarant',
                'login-first-letter-prenom' => 'N',
                'login-first-letter-nom' => 'D',
                'login-code-postal' => '75020',
                '_csrf_token' => 'token456',
            ],
            CodeSuiviLoginAuthenticator::INVALID_EMAIL_MESSAGE,
        ];
    }

    public function testAuthenticateWithoutEmailWhenUsager2faDisabledSucceeds(): void
    {
        $signalement = self::createMockSignalement(ProfileDeclarant::LOCATAIRE, 'Martin', 'Luc', '13001', '12345678', '');
        $signalement->setMailOccupant(null);
        $request = new Request([], [
            'visitor-type' => 'occupant',
            'login-first-letter-prenom' => 'L',
            'login-first-letter-nom' => 'M',
            'login-code-postal' => '13001',
            '_csrf_token' => 'token123',
        ], ['code' => '12345678']);

        $this->signalementRepository->method('findOneByCodeForPublic')->willReturn($signalement);
        $this->signalementUserProvider->method('getUsagerData')->willReturn([
            'identifier' => '12345678:occupant',
            'email' => null,
            'user' => new User(),
        ]);

        $passport = $this->createAuthenticator(usager2faEnabled: false)->authenticate($request);
        $this->assertInstanceOf(SignalementUser::class, $passport->getUser());
    }

    public function testStartWithoutCodeRendersSessionExpiredPage(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with('front/flash-messages.html.twig')
            ->willReturn('<p>page session expirée</p>');
        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('/2fa-suivi');
        $request->setSession($session);

        $response = $this->createAuthenticator(twig: $twig)->start($request);

        $this->assertNotInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('<p>page session expirée</p>', $response->getContent());
        $flashes = $session->getFlashBag()->get('error');
        $this->assertCount(1, $flashes);
        $this->assertSame('Session expirée', $flashes[0]['title']);
    }

    public function testStartWithCodeRedirectsToLoginPage(): void
    {
        $this->urlGenerator->method('generate')->willReturnCallback(
            static fn (string $route, array $params = []): string => '/authentification/'.($params['code'] ?? '')
        );
        $request = Request::create('/suivre-mon-signalement/12345678');
        $request->attributes->set('code', '12345678');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $response = $this->createAuthenticator()->start($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/authentification/12345678', $response->getTargetUrl());
    }

    private function createAuthenticator(bool $usager2faEnabled = false, ?Environment $twig = null): CodeSuiviLoginAuthenticator
    {
        return new CodeSuiviLoginAuthenticator(
            $this->urlGenerator,
            $this->signalementRepository,
            $this->signalementUserProvider,
            new TwoFactorCondition(true, false, $usager2faEnabled),
            $twig ?? $this->createStub(Environment::class),
        );
    }

    private static function createMockSignalement(
        ProfileDeclarant $profileDeclarant,
        string $nom,
        string $prenom,
        string $codePostal,
        string $codeSuivi,
        string $email,
    ): Signalement {
        $signalement = (new Signalement())
           ->setNomOccupant($nom)
           ->setPrenomOccupant($prenom)
           ->setCodeSuivi($codeSuivi)
           ->setMailOccupant($email)
           ->setProfileDeclarant($profileDeclarant);

        $address = (new Address())
            ->setPostCode($codePostal)
            ->setTerritory(new Territory());
        $signalement->setAddress($address);

        if (ProfileDeclarant::TIERS_PARTICULIER === $profileDeclarant
                || ProfileDeclarant::TIERS_PRO === $profileDeclarant
                || ProfileDeclarant::BAILLEUR === $profileDeclarant
                || ProfileDeclarant::SERVICE_SECOURS === $profileDeclarant
        ) {
            $signalement->setNomDeclarant($nom);
            $signalement->setPrenomDeclarant($prenom);
        }

        return $signalement;
    }
}
