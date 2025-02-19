<?php

namespace App\Tests\Functional\Controller\Api;

use App\Entity\File;
use App\Entity\User;
use App\Repository\SignalementRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class SignalementFileUpdateControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private RouterInterface $router;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy([
            'email' => 'api-01@histologe.fr',
        ]);

        $this->client->loginUser($user, 'api');
        $this->router = self::getContainer()->get('router');
    }

    public function testUpdateSignalementFile(): void
    {
        $signalement = self::getContainer()->get(SignalementRepository::class)->find(1);
        $file = $signalement->getFiles()->filter(function (File $file) {
            return 'photo' === $file->getFileType();
        })->current();

        $payload = [
            'documentType' => 'PHOTO_SITUATION',
            'description' => 'lorem ipsum dolor sit amet',
        ];
        $this->client->request(
            method: 'PATCH',
            uri: $this->router->generate('api_signalements_files_patch', ['uuid' => $file->getUuid()]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertResponseIsSuccessful();
        $this->assertEquals($payload['documentType'], $response['documentType']);
    }

    public function testUpdateSignalementFileWithIncorrectType(): void
    {
        $signalement = self::getContainer()->get(SignalementRepository::class)->find(1);
        $file = $signalement->getFiles()->filter(function (File $file) {
            return 'photo' === $file->getFileType();
        })->current();

        $payload = [
            'documentType' => 'PROCEDURE_ARRETE_PREFECTORAL',
            'description' => 'lorem ipsum dolor sit amet',
        ];
        $this->client->request(
            method: 'PATCH',
            uri: $this->router->generate('api_signalements_files_patch', ['uuid' => $file->getUuid()]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertResponseIsSuccessful();
        $this->assertEquals('AUTRE', $response['documentType']);
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
            content: json_encode($payload)
        );

        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }
}
