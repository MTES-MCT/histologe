<?php

namespace App\Tests\Functional\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SignalementControllerTest extends WebTestCase
{
    public function testGetSignalementList(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy([
            'email' => 'api-01@histologe.fr',
        ]);
        $client->loginUser($user, 'api');
        $client->request('GET', '/api/signalements');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(5, $response);
    }

    public function testGetSignalementListWithLimit(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy([
            'email' => 'api-01@histologe.fr',
        ]);
        $client->loginUser($user, 'api');
        $client->request('GET', '/api/signalements?limit=2');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(2, $response);
    }

    public function testGetSignalementByUuid(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy([
            'email' => 'api-02@histologe.fr',
        ]);
        $client->loginUser($user, 'api');
        $client->request('GET', '/api/signalements/00000000-0000-0000-2024-000000000012');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('desordres', $response);
        $this->assertCount(7, $response['desordres']);
    }

    public function testGetOldSignalementByUuid(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy([
            'email' => 'api-01@histologe.fr',
        ]);
        $client->loginUser($user, 'api');
        $client->request('GET', '/api/signalements/00000000-0000-0000-2022-000000000001');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('desordres', $response);
        $this->assertCount(3, $response['desordres']);
    }

    public function testGetSignalementListWithErrorsFilter(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy([
            'email' => 'api-01@histologe.fr',
        ]);
        $client->loginUser($user, 'api');
        $client->request('GET', '/api/signalements?limit=115151&dateAffectationDebut=2022-01-010000&dateAffectationFin=2022-01-01000');
        $this->assertEquals(400, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(4, $response['errors']);
        $this->assertArrayHasKey('property', current($response['errors']));
        $this->assertArrayHasKey('message', current($response['errors']));
        $this->assertArrayHasKey('invalidValue', current($response['errors']));
    }
}
