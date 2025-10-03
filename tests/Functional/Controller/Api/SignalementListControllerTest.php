<?php

namespace App\Tests\Functional\Controller\Api;

use App\Entity\Signalement;
use App\Entity\User;
use App\Tests\ApiHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SignalementListControllerTest extends WebTestCase
{
    use ApiHelper;

    public function testGetSignalementList(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy([
            'email' => 'api-01@signal-logement.fr',
        ]);
        $client->loginUser($user, 'api');
        $client->request('GET', '/api/signalements');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent(), true);

        foreach ($response as $signalement) {
            $this->assertIsArray($signalement['affectations']);
            if ('2023-26' === $signalement['reference']) {
                $this->assertCount(3, $signalement['affectations']);
            }
        }
        $this->assertCount(9, $response);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($client);
    }

    public function testGetSignalementListWithLimit(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy([
            'email' => 'api-01@signal-logement.fr',
        ]);
        $client->loginUser($user, 'api');
        $client->request('GET', '/api/signalements?limit=2');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(2, $response);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($client);
    }

    /**
     * @dataProvider provideDataSignalementByUuid
     */
    public function testGetSignalementByUuid(string $email, string $uuid, int $nbAffectations, int $nbDesordres): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);
        $client->loginUser($user, 'api');
        $client->request('GET', '/api/signalements/'.$uuid);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('desordres', $response);
        $this->assertArrayHasKey('affectations', $response);
        $this->assertCount($nbDesordres, $response['desordres']);
        $this->assertCount($nbAffectations, $response['affectations']);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($client);
    }

    public function provideDataSignalementByUuid(): \Generator
    {
        yield 'api-02 user with signalement 2024-12' => ['api-02@signal-logement.fr', '00000000-0000-0000-2024-000000000012', 1, 7];
        yield 'api-01 user with signalement 2023-26' => ['api-01@signal-logement.fr', '00000000-0000-0000-2023-000000000026', 3, 0];
        yield 'api-01 user with old signalement 2022-03' => ['api-01@signal-logement.fr', '00000000-0000-0000-2022-000000000003', 1, 3];
    }

    /**
     * @dataProvider provideQueryParameters
     *
     * @param array<string> $queryParameters
     */
    public function testGetSignalementListWithErrorsFilter(array $queryParameters, int $countErrors): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy([
            'email' => 'api-01@signal-logement.fr',
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

    public function testGetUnAffectedSignalementByUuid(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'api-01@signal-logement.fr']);

        $uuid = '00000000-0000-0000-2024-000000000006';
        $client->loginUser($user, 'api');
        $client->request('GET', '/api/signalements/'.$uuid);
        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        $this->hasXrequestIdHeaderAndOneApiRequestLog($client);
    }

    public function testGetUnAffectedSignalementCreatedByMeByUuid(): void
    {
        $client = static::createClient();
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy(['email' => 'api-01@signal-logement.fr']);

        $uuid = '00000000-0000-0000-2024-000000000006';
        $signalement = self::getContainer()->get('doctrine')->getRepository(Signalement::class)->findOneBy(['uuid' => $uuid]);
        $signalement->setCreatedBy($user);
        self::getContainer()->get('doctrine')->getManager()->flush();

        $client->loginUser($user, 'api');
        $client->request('GET', '/api/signalements/'.$uuid);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->hasXrequestIdHeaderAndOneApiRequestLog($client);
    }
}
