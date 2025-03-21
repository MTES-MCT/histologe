<?php

namespace App\Tests\Functional\Controller;

use App\Repository\UserRepository;
use App\Tests\ApiHelper;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class SecurityControllerTest extends WebTestCase
{
    use ApiHelper;
    use SessionHelper;

    /** @dataProvider provideJsonLogin  */
    public function testJsonLogin(?int $status = null, ?string $email = null, ?string $password = null): void
    {
        $client = static::createClient();
        $payload = [
            'email' => $email,
            'password' => $password,
        ];
        $client->request('POST', '/api/login', [], [], [], json_encode($payload));
        $this->assertResponseStatusCodeSame($status);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($client);
    }

    public function provideJsonLogin(): \Generator
    {
        yield 'Success login with ROLE_API_USER' => [
            'status' => Response::HTTP_OK,
            'email' => 'api-01@histologe.fr',
            'password' => 'histologe',
        ];

        yield 'Failed login without ROLE_API_USER' => [
            'status' => Response::HTTP_UNAUTHORIZED,
            'email' => 'admin-territory-13@histologe.fr',
            'password' => 'histologe',
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
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
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
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
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
}
