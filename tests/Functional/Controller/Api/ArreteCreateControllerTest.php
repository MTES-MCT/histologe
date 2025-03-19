<?php

namespace App\Tests\Functional\Controller\Api;

use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Tests\ApiHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class ArreteCreateControllerTest extends WebTestCase
{
    use ApiHelper;
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

    /** @dataProvider providePayloadSuccess */
    public function testCreateArreteSuccess(string $type, array $payload): void
    {
        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_arretes_post', [
                'uuid' => '00000000-0000-0000-2022-000000000006',
            ]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );
        $signalement = self::getContainer()->get(SignalementRepository::class)->findOneBy(['uuid' => '00000000-0000-0000-2022-000000000006']);

        $lastDescription = $signalement->getSuivis()->last()->getDescription();

        foreach ($payload as $key => $value) {
            if ('arrete_mainlevee' === $type && 'type' === $key) {
                continue;
            }
            $this->assertStringContainsString($value, $lastDescription);
        }
        $this->assertEmailCount(1);
        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    /** @dataProvider providePayloadFailure */
    public function testCreateArreteFailed(array $payload): void
    {
        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_arretes_post', [
                'uuid' => '00000000-0000-0000-2022-000000000006',
            ]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    public function providePayloadFailure(): \Generator
    {
        yield 'Wrong payload with future date' => [
            [
                'date' => '2225-03-11',
                'numero' => '2023/DD13/00664',
                'type' => 'Arrêté L.511-11 - Suroccupation',
            ],
        ];
        yield 'Wrong payload with missing date arrêté' => [
            [
                'numero' => '2023/DD13/00664',
                'type' => 'Arrêté L.511-11 - Suroccupation',
            ],
        ];
        yield 'Wrong payload with date arrete greater than date mainlevee' => [
            [
                'date' => '2025-03-11',
                'numero' => '2023/DD13/00664',
                'type' => 'Arrêté L- Suroccupation',
                'mainLeveeDate' => '2025-03-10',
                'mainLeveeNumero' => '2023-DD13-00172',
            ],
        ];
    }

    public function providePayloadSuccess(): \Generator
    {
        yield 'Payload arrêté' => [
            'arrete',
            [
                'date' => '2025-03-11',
                'numero' => '2023/DD13/00664',
                'type' => 'Arrêté L.511-11 - Suroccupation',
            ],
        ];
        yield 'Payload arrêté with mainlevee' => [
            'arrete_mainlevee',
            [
                'date' => '2025-03-11',
                'numero' => '2023/DD13/00664',
                'type' => 'Arrêté L.511-11 - Suroccupation',
                'mainLeveeDate' => '2025-03-13',
                'mainLeveeNumero' => '2023-DD13-00172',
            ],
        ];
    }
}
