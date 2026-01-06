<?php

namespace App\Tests\Functional\Controller\Api;

use App\Entity\User;
use App\Entity\UserApiPermission;
use App\Repository\SignalementRepository;
use App\Service\ImageManipulationHandler;
use App\Service\UploadHandlerService;
use App\Tests\ApiHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class SignalementFileUploadControllerTest extends WebTestCase
{
    use ApiHelper;
    private KernelBrowser $client;
    private User $user;
    private RouterInterface $router;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy([
            'email' => 'api-01@signal-logement.fr',
        ]);

        $this->router = self::getContainer()->get('router');

        $this->client->loginUser($this->user, 'api');
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
        $signalement = self::getContainer()->get(SignalementRepository::class)->findOneBy(['uuid' => $uuid]);

        $affectation = $signalement->getAffectations()->first();
        if (!$affectation) {
            $this->fail('No affectation found for the signalement');
        }
        $partnerUuid = $affectation->getPartner()->getUuid();
        $this->postRequest($uuid, $partnerUuid, [$imageFile, $documentFile]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    public function testFileUploadOnUnaffectedSignalement(): void
    {
        $imageFile = new UploadedFile(
            __DIR__.'/../../../files/sample.jpg',
            'sample.jpg',
            'image/jpeg',
            null,
            true
        );

        $imageManipulationHandler = $this->createMock(ImageManipulationHandler::class);
        self::getContainer()->set(ImageManipulationHandler::class, $imageManipulationHandler);

        $uploadHandlerServiceMock = $this->createMock(UploadHandlerService::class);
        $uploadHandlerServiceMock
            ->method('uploadFromFile')
            ->willReturn('sample.jpg');

        self::getContainer()->set(UploadHandlerService::class, $uploadHandlerServiceMock);
        $uuid = '00000000-0000-0000-2024-000000000006';
        $permissionParams = ['user' => $this->user, 'partnerType' => null, 'territory' => null];
        $partner = self::getContainer()->get('doctrine')->getRepository(UserApiPermission::class)->findOneBy($permissionParams)->getPartner();
        $this->postRequest($uuid, $partner->getUuid(), [$imageFile]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    public function testFileUploadOnUnaffectedSignalementCreatedByMe(): void
    {
        $imageFile = new UploadedFile(
            __DIR__.'/../../../files/sample.jpg',
            'sample.jpg',
            'image/jpeg',
            null,
            true
        );

        $imageManipulationHandler = $this->createMock(ImageManipulationHandler::class);
        self::getContainer()->set(ImageManipulationHandler::class, $imageManipulationHandler);

        $uploadHandlerServiceMock = $this->createMock(UploadHandlerService::class);
        $uploadHandlerServiceMock
            ->method('uploadFromFile')
            ->willReturn('sample.jpg');

        self::getContainer()->set(UploadHandlerService::class, $uploadHandlerServiceMock);
        $uuid = '00000000-0000-0000-2024-000000000006';
        $permissionParams = ['user' => $this->user, 'partnerType' => null, 'territory' => null];
        $partner = self::getContainer()->get('doctrine')->getRepository(UserApiPermission::class)->findOneBy($permissionParams)->getPartner();
        $signalement = self::getContainer()->get(SignalementRepository::class)->findOneBy(['uuid' => $uuid]);
        $signalement->setCreatedBy($this->user);
        $signalement->setCreatedByPartner($partner);
        self::getContainer()->get('doctrine')->getManager()->flush();
        $this->postRequest($uuid, $partner->getUuid(), [$imageFile]);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    /**
     * @dataProvider provideErrorInput
     */
    public function testFileUploadWithBadRequest(string $uuid, int $codeHttpStatus): void
    {
        $this->postRequest($uuid, '', []);
        $this->assertResponseStatusCodeSame($codeHttpStatus);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    public function provideErrorInput(): \Generator
    {
        yield 'test upload with bad request' => ['00000000-0000-0000-2022-000000000006', Response::HTTP_FORBIDDEN];
        yield 'test upload with not found' => ['00000000-0000-0000-2022-000000000000', Response::HTTP_NOT_FOUND];
        yield 'test upload with forbidden' => ['00000000-0000-0000-2022-000000000004', Response::HTTP_FORBIDDEN];
    }

    /**
     * @param array<UploadedFile> $files
     */
    private function postRequest(string $uuid, string $partnerUuid, array $files = []): void
    {
        $this->client->request(
            'POST',
            $this->router->generate('api_signalements_files_post', ['uuid' => $uuid]),
            ['partenaireUuid' => $partnerUuid],
            ['files' => $files],
            ['CONTENT_TYPE' => 'multipart/form-data']
        );
    }
}
