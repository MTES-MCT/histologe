<?php

namespace App\Tests\Functional\Controller\Api;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SignalementControllerTest extends WebTestCase
{
    public function testGetSignalementList()
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

    public function testGetSignalementListWithLimit()
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

    public function testGetSignalementByUuid()
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

    public function testGetOldSignalementByUuid()
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
}
