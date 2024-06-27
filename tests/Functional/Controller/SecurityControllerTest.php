<?php

namespace App\Tests\Functional\Controller;

use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SecurityControllerTest extends WebTestCase
{
    use SessionHelper;

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

        $this->assertResponseStatusCodeSame(404);
    }

    /*public function testShowUploadedWithValidToken(): void
    {
        $client = static::createClient();
        $token = $this->generateCsrfToken($client, 'suivi_signalement_ext_file_view');
        $client->request('GET', '/_up/check.png?t=' . $token);

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
    }*/
}
