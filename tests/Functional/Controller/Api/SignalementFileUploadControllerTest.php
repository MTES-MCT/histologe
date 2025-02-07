<?php

namespace App\Tests\Functional\Controller\Api;

use App\Entity\User;
use App\Service\ImageManipulationHandler;
use App\Service\UploadHandlerService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class SignalementFileUploadControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private RouterInterface $router;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy([
            'email' => 'api-01@histologe.fr',
        ]);

        $this->router = self::getContainer()->get('router');

        $this->client->loginUser($user, 'api');
    }

    public function testFileUploadSuccess(): void
    {
        $imageFile = new UploadedFile(
            __DIR__.'/../../../files/sample.jpg',
            'sample.jpg',
            'image/jpeg',
            null,
            true
        );

        $documentFile = new UploadedFile(
            __DIR__.'/../../../files/sample.pdf',
            'sample.pdf',
            'application/pdf',
            null,
            true
        );

        $imageManipulationHandler = $this->createMock(ImageManipulationHandler::class);
        self::getContainer()->set(ImageManipulationHandler::class, $imageManipulationHandler);

        $uploadHandlerServiceMock = $this->createMock(UploadHandlerService::class);
        $uploadHandlerServiceMock
            ->method('uploadFromFile')
            ->willReturnOnConsecutiveCalls('sample.jpg', '');

        self::getContainer()->set(UploadHandlerService::class, $uploadHandlerServiceMock);
        $uuid = '00000000-0000-0000-2022-000000000006';
        $this->postRequest($uuid, [$imageFile, $documentFile]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
    }

    /**
     * @dataProvider provideErrorInput
     */
    public function testFileUploadWithBadRequest(string $uuid, int $codeHttpStatus): void
    {
        $this->postRequest($uuid, []);
        $this->assertEquals($codeHttpStatus, $this->client->getResponse()->getStatusCode());
    }

    public function provideErrorInput(): \Generator
    {
        yield 'test upload with bad request' => ['00000000-0000-0000-2022-000000000006', Response::HTTP_BAD_REQUEST];
        yield 'test upload with not found' => ['00000000-0000-0000-2022-000000000000', Response::HTTP_NOT_FOUND];
        yield 'test upload with forbidden' => ['00000000-0000-0000-2022-000000000004', Response::HTTP_FORBIDDEN];
    }

    private function postRequest(string $uuid, array $files = []): void
    {
        $this->client->request(
            'POST',
            $this->router->generate('api_signalements_files_post', ['uuid' => $uuid]),
            [],
            ['files' => $files],
            ['CONTENT_TYPE' => 'multipart/form-data']
        );
    }
}
