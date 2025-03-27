<?php

namespace App\Tests\Functional\Controller\Api;

use App\Entity\User;
use App\Tests\ApiHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SignalementControllerTest extends WebTestCase
{
    use ApiHelper;

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
        $this->assertCount(6, $response);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($client);
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
        $this->hasXrequestIdHeaderAndOneApiRequestLog($client);
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
        $this->hasXrequestIdHeaderAndOneApiRequestLog($client);
    }

    public function testGetOldSignalementByUuid(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy([
            'email' => 'api-01@histologe.fr',
        ]);
        $client->loginUser($user, 'api');
        $client->request('GET', '/api/signalements/00000000-0000-0000-2022-000000000003');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('desordres', $response);
        $this->assertCount(3, $response['desordres']);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($client);
    }

    /**
     * @dataProvider provideQueryParameters
     */
    public function testGetSignalementListWithErrorsFilter(array $queryParameters, int $countErrors): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy([
            'email' => 'api-01@histologe.fr',
        ]);
        $client->loginUser($user, 'api');
        $client->request('GET', '/api/signalements?'.http_build_query($queryParameters));
        $this->assertEquals(400, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount($countErrors, $response['errors']);
        $this->assertArrayHasKey('property', current($response['errors']));
        $this->assertArrayHasKey('message', current($response['errors']));
        $this->assertArrayHasKey('invalidValue', current($response['errors']));
        $this->hasXrequestIdHeaderAndOneApiRequestLog($client);
    }

    public function provideQueryParameters(): \Generator
    {
        yield 'Out of range limit' => [['limit' => '115151'], 1];
        yield 'Wrong type limit' => [['limit' => 'hello'], 2];
        yield 'Wrong type page' => [['page' => 'world'], 1];
        yield 'Wrong format page' => [['page' => '-1'], 1];
        yield 'Wrong dateAffectation format ' => [
            [
                'dateAffectationDebut' => '2022-01-01000',
                'dateAffectationFin' => '2022-01-010000',
            ], 2,
        ];
        yield 'Wrong query value parameters ' => [
            [
                'limit' => 'hello',
                'page' => 'world',
                'dateAffectationDebut' => 'my',
                'dateAffectationFin' => 'friend',
            ],
            6,
        ];
    }
}
