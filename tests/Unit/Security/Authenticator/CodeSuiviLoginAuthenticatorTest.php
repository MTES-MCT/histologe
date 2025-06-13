<?php

namespace App\Tests\Unit\Security\Authenticator;

use App\Entity\Enum\ProfileDeclarant;
use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Security\Authenticator\CodeSuiviLoginAuthenticator;
use App\Security\Provider\SignalementUserProvider;
use App\Security\User\SignalementUser;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class CodeSuiviLoginAuthenticatorTest extends TestCase
{
    use FixturesHelper;

    private MockObject|SignalementRepository $signalementRepository;
    private MockObject|SignalementUserProvider $signalementUserProvider;
    private MockObject|UrlGeneratorInterface $urlGenerator;

    protected function setUp(): void
    {
        $this->signalementRepository = $this->createMock(SignalementRepository::class);
        $this->signalementUserProvider = $this->createMock(SignalementUserProvider::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
    }

    /**
     * @dataProvider provideSuccessfulAuthenticationCases
     */
    public function testAuthenticateSuccess(
        Signalement $signalement,
        array $requestData,
        array $expectedUserData,
    ): void {
        $request = new Request([], $requestData);

        $this->signalementRepository->method('findOneByCodeForPublic')->willReturn($signalement);
        $this->signalementUserProvider->method('getUsagerData')->willReturn($expectedUserData);

        $authenticator = new CodeSuiviLoginAuthenticator(
            $this->urlGenerator,
            $this->signalementRepository,
            $this->signalementUserProvider
        );

        $passport = $authenticator->authenticate($request);
        $this->assertInstanceOf(SignalementUser::class, $passport->getUser());
        $this->assertTrue(in_array($expectedUserData['roles'], $passport->getUser()->getRoles()));
    }

    public static function provideSuccessfulAuthenticationCases(): \Generator
    {
        yield 'occupant' => [
            (new self())->getMockSignalement(
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
            (new self())->getMockSignalement(
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
            (new self())->getMockSignalement(
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
            (new self())->getMockSignalement(
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
            (new self())->getMockSignalement(
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

    public function testAuthenticateWithInvalidCodeThrowsException()
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Code de suivi invalide');

        $request = new Request([], ['code' => 'wrongcode']);
        $this->signalementRepository->method('findOneByCodeForPublic')->willReturn(null);
        $authenticator = new CodeSuiviLoginAuthenticator($this->urlGenerator, $this->signalementRepository, $this->signalementUserProvider);
        $authenticator->authenticate($request);
    }

    public function testAuthenticateWithWrongInitialsThrowsException()
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Informations incorrectes');

        $signalement = $this->getSignalementLocataire();
        $request = new Request([], [
            'code' => '12345678',
            'visitor-type' => 'occupant',
            'login-first-letter-prenom' => 'X', // Mauvaise initiale
            'login-first-letter-nom' => 'Y',     // Mauvaise initiale
            'login-code-postal' => '13001',
            '_csrf_token' => 'token123',
        ]);

        $this->signalementRepository->method('findOneByCodeForPublic')->willReturn($signalement);

        $authenticator = new CodeSuiviLoginAuthenticator(
            $this->urlGenerator,
            $this->signalementRepository,
            $this->signalementUserProvider
        );
        $authenticator->authenticate($request);
    }

    public function testAuthenticateWithMissingVisitorTypeThrowsException()
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Merci de sélectionner si vous occupez le logement ou si vous avez fait la déclaration.');
        $signalement = $this->getSignalement(profileDeclarant: ProfileDeclarant::TIERS_PRO, nom: 'Martin', prenom: 'Luc', codePostal: '13001', codeSuivi: '12345678');

        $request = new Request([], [
            'code' => '12345678',
            'login-first-letter-prenom' => 'L',
            'login-first-letter-nom' => 'M',
            'login-code-postal' => '13001',
            '_csrf_token' => 'token123',
        ]);

        $this->signalementRepository->method('findOneByCodeForPublic')->willReturn($signalement);

        $authenticator = new CodeSuiviLoginAuthenticator($this->urlGenerator, $this->signalementRepository, $this->signalementUserProvider);
        $authenticator->authenticate($request);
    }

    private function getMockSignalement(
        ProfileDeclarant $profileDeclarant,
        string $nom,
        string $prenom,
        string $codePostal,
        string $codeSuivi,
        string $email,
    ): Signalement {
        return $this->getSignalement(
            profileDeclarant: $profileDeclarant,
            nom: $nom,
            prenom: $prenom,
            codePostal: $codePostal,
            codeSuivi: $codeSuivi,
            email: $email,
        );
    }
}
