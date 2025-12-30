<?php

namespace App\Tests\Functional\Controller\Api;

use App\Entity\User;
use App\Repository\FileRepository;
use App\Repository\SignalementRepository;
use App\Tests\ApiHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class SignalementFileUpdateControllerTest extends WebTestCase
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

        $this->client->loginUser($this->user, 'api');
        $this->router = self::getContainer()->get('router');
    }

    public function testUpdateSignalementFile(): void
    {
        $signalement = self::getContainer()->get(SignalementRepository::class)->findOneBy(['reference' => '2023-26']);
        $file = self::getContainer()->get(FileRepository::class)->findOneBy(['signalement' => $signalement, 'extension' => 'jpg']);

        $payload = [
            'documentType' => 'PHOTO_SITUATION',
            'description' => 'lorem ipsum dolor sit amet',
        ];
        $this->client->request(
            method: 'PATCH',
            uri: $this->router->generate('api_signalements_files_patch', ['uuid' => $file->getUuid()]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode($payload)
        );

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertResponseIsSuccessful();
        $this->assertEquals($payload['documentType'], $response['documentType']);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    public function testUpdateSignalementFileWithFileTypeUpdated(): void
    {
        $signalement = self::getContainer()->get(SignalementRepository::class)->findOneBy(['reference' => '2023-26']);
        $file = self::getContainer()->get(FileRepository::class)->findOneBy(['signalement' => $signalement, 'extension' => 'pdf']);

        $payload = [
            'documentType' => 'PROCEDURE_ARRETE_PREFECTORAL',
            'description' => 'lorem ipsum dolor sit amet',
        ];
        $this->client->request(
            method: 'PATCH',
            uri: $this->router->generate('api_signalements_files_patch', ['uuid' => $file->getUuid()]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode($payload)
        );

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertResponseIsSuccessful();
        $this->assertNull($response['description']);
        $this->assertEquals('PROCEDURE_ARRETE_PREFECTORAL', $response['documentType']);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    public function testUpdateSignalementFileWithFileNotFound(): void
    {
        $payload = [
            'documentType' => 'PROCEDURE_ARRETE_PREFECTORAL',
        ];
        $this->client->request(
            method: 'PATCH',
            uri: $this->router->generate('api_signalements_files_patch', ['uuid' => '1234']),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    public function testUpdateFileOnUnaffectedSignalement(): void
    {
        $signalement = self::getContainer()->get(SignalementRepository::class)->findOneBy(['reference' => '2022-1']);
        $file = self::getContainer()->get(FileRepository::class)->findOneBy(['signalement' => $signalement, 'extension' => 'jpg']);

        $payload = [
            'documentType' => 'PHOTO_SITUATION',
            'description' => 'lorem ipsum dolor sit amet',
        ];
        $this->client->request(
            method: 'PATCH',
            uri: $this->router->generate('api_signalements_files_patch', ['uuid' => $file->getUuid()]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    public function testUpdateFileOnUnaffectedSignalementCreatedByMe(): void
    {
        $signalement = self::getContainer()->get(SignalementRepository::class)->findOneBy(['reference' => '2022-1']);
        $file = self::getContainer()->get(FileRepository::class)->findOneBy(['signalement' => $signalement, 'extension' => 'jpg']);
        $signalement->setCreatedBy($this->user);
        self::getContainer()->get('doctrine')->getManager()->flush();

        $payload = [
            'documentType' => 'PHOTO_SITUATION',
            'description' => 'lorem ipsum dolor sit amet',
        ];
        $this->client->request(
            method: 'PATCH',
            uri: $this->router->generate('api_signalements_files_patch', ['uuid' => $file->getUuid()]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode($payload)
        );

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertResponseIsSuccessful();
        $this->assertEquals($payload['documentType'], $response['documentType']);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }
}
