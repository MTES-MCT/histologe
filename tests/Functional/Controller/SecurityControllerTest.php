<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Security\User\SignalementUser;
use App\Tests\ApiHelper;
use App\Tests\SessionHelper;
use App\Tests\UserHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class SecurityControllerTest extends WebTestCase
{
    use ApiHelper;
    use SessionHelper;
    use UserHelper;
    private const string SIGN_2025_11_UUID = '00000000-0000-0000-2025-000000000011';

    public function testLoginBailleurWithValidLoginPOST(): void
    {
        $client = static::createClient();
        $signalement = $client->getContainer()->get(SignalementRepository::class)->findOneBy(['uuid' => self::SIGN_2025_11_UUID]);
        $payload = [
            'bailleur_reference' => $signalement->getReference(),
            'bailleur_code' => $signalement->getLoginBailleur(),
            '_csrf_token' => $this->generateCsrfToken($client, 'authenticate'),
        ];
        $client->request('POST', '/login-bailleur', $payload);
        $this->assertResponseRedirects('/dossier-bailleur/');
    }

    public function testLoginBailleurFailedWithValidLoginGET(): void
    {
        $client = static::createClient();
        $signalement = $client->getContainer()->get(SignalementRepository::class)->findOneBy(['uuid' => self::SIGN_2025_11_UUID]);
        $client->request('GET', '/login-bailleur', [
            'bailleur_reference' => $signalement->getReference(),
            'bailleur_code' => $signalement->getLoginBailleur(),
        ]);
        $this->assertAnySelectorTextSame('h1', 'Accéder à mon dossier bailleur');
    }

    public function testLoginBailleurWithInvalidLoginPOST(): void
    {
        $client = static::createClient();
        $signalement = $client->getContainer()->get(SignalementRepository::class)->findOneBy(['uuid' => self::SIGN_2025_11_UUID]);
        $payload = [
            'bailleur_reference' => $signalement->getReference(),
            'bailleur_code' => 'invalid_code',
            '_csrf_token' => $this->generateCsrfToken($client, 'authenticate'),
        ];
        $client->request('POST', '/login-bailleur', $payload);
        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $this->assertResponseRedirects('/login-bailleur');
        $client->followRedirect();
        $this->assertSelectorExists('.fr-alert.fr-alert--error');
    }

    public function testFOLoginOnOccupantWithoutEmail(): void
    {
        $client = static::createClient();
        $signalement = $client->getContainer()->get(SignalementRepository::class)->findOneBy(['reference' => '2022-1']);
        $payload = [
            'code' => $signalement->getCodeSuivi(),
            'visitor-type' => 'occupant',
            'login-first-letter-prenom' => mb_substr($signalement->getPrenomOccupant(), 0, 1),
            'login-first-letter-nom' => mb_substr($signalement->getNomOccupant(), 0, 1),
            'login-code-postal' => $signalement->getCpOccupant(),
            '_csrf_token' => $this->generateCsrfToken($client, 'authenticate'),
        ];
        $client->request('POST', '/authentification/'.$signalement->getCodeSuivi(), $payload);
        $this->assertResponseRedirects();

        $container = $client->getContainer();
        $tokenStorage = $container->get('security.token_storage');
        $token = $tokenStorage->getToken();

        $this->assertNotNull($token);
        $this->assertInstanceOf(SignalementUser::class, $token->getUser());
        $this->assertInstanceOf(User::class, $token->getUser()->getUser());
        $this->assertEquals($signalement->getCodeSuivi(), $token->getUser()->getCodeSuivi());
        $this->assertEquals('', $token->getUser()->getEmail());
        $this->assertStringStartsWith('sl__', $token->getUser()->getUser()->getEmail());
    }

    /** @dataProvider provideJsonLogin  */
    public function testJsonLogin(?int $status = null, ?string $email = null, ?string $password = null): void
    {
        $client = static::createClient();
        $payload = [
            'email' => $email,
            'password' => $password,
        ];
        $client->request('POST', '/api/login', [], [], [], (string) json_encode($payload));
        $this->assertResponseStatusCodeSame($status);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($client);
    }

    public function provideJsonLogin(): \Generator
    {
        yield 'Success login with ROLE_API_USER' => [
            'status' => Response::HTTP_OK,
            'email' => 'api-01@signal-logement.fr',
            'password' => 'signallogement',
        ];

        yield 'Failed login without ROLE_API_USER' => [
            'status' => Response::HTTP_UNAUTHORIZED,
            'email' => 'admin-territory-13@signal-logement.fr',
            'password' => 'signallogement',
        ];

        yield 'Failed login with credentials empty' => [
            'status' => Response::HTTP_UNAUTHORIZED,
            'email' => '',
            'password' => '',
        ];

        yield 'Failed login without payload' => [
            'status' => Response::HTTP_UNAUTHORIZED,
        ];
    }

    public function testShowUploadedFileSucceed(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);
        $client->request('GET', '/_up/check.png/00000000-0000-0000-2022-000000000001');
        /** @var BinaryFileResponse $response */
        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
    }

    public function testShowUploadedFileFailed(): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        $client->request('GET', '/_up/file_not_exist.txt/00000000-0000-0000-2022-000000000001');
        /** @var BinaryFileResponse $response */
        $response = $client->getResponse();
        $this->assertEquals('image-404.png', $response->getFile()->getFilename());
        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
    }

    public function testShowUploadedWithInvalidToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_up/check.png');

        $this->assertResponseRedirects('/connexion');
    }

    public function testShowExportPdfUsagerLogged(): void
    {
        $_GET['folder'] = '_up';
        $client = static::createClient();
        $uuid = '00000000-0000-0000-2024-000000000012';
        $filename = 'export-pdf-signalement-'.$uuid.'.pdf';
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $signalement = $signalementRepository->findOneBy(['uuid' => $uuid]);
        $codeSuivi = $signalement->getCodeSuivi();

        $signalementUser = $this->getSignalementUser($signalement);
        $client->loginUser($signalementUser, 'code_suivi');

        $client->request(
            'GET',
            '/show-export-pdf-usager/'.$filename.'/'.$codeSuivi.'?folder=_up',
        );
        /** @var BinaryFileResponse $response */
        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
    }

    public function testShowExportPdfUsagerNotLogged(): void
    {
        $_GET['folder'] = '_up';
        $client = static::createClient();
        $uuid = '00000000-0000-0000-2022-000000000001';
        $filename = 'export-pdf-signalement-'.$uuid.'.pdf';
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $signalement = $signalementRepository->findOneBy(['uuid' => $uuid]);
        $codeSuivi = $signalement->getCodeSuivi();

        $client->request(
            'GET',
            '/show-export-pdf-usager/'.$filename.'/'.$codeSuivi.'?folder=_up',
        );
        $this->assertResponseRedirects('/authentification/'.$codeSuivi);
    }
}
