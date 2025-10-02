<?php

namespace App\Tests\Functional\Controller\Api;

use App\Entity\Enum\NotificationType;
use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Repository\PartnerRepository;
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
            'email' => 'api-01@signal-logement.fr',
        ]);

        $this->router = self::getContainer()->get('router');

        $this->client->loginUser($user, 'api');
    }

    /**
     * @dataProvider providePayloadSuccess
     *
     * @param array<mixed> $payload
     */
    public function testCreateArreteSuccess(string $type, array $payload): void
    {
        $signalementUuid = '00000000-0000-0000-2022-000000000006';
        $signalement = self::getContainer()->get(SignalementRepository::class)->findOneBy(['uuid' => $signalementUuid]);
        $payload['partenaireUuid'] = $signalement->getAffectations()->first()->getPartner()->getUuid();
        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_arretes_post', [
                'uuid' => $signalementUuid,
            ]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );
        $signalement = self::getContainer()->get(SignalementRepository::class)->findOneBy(['uuid' => $signalementUuid]);

        $lastDescription = $signalement->getSuivis()->last()->getDescription();

        foreach ($payload as $key => $value) {
            if (('arrete_mainlevee' === $type && 'type' === $key) || 'partenaireUuid' === $key) {
                continue;
            }
            $this->assertStringContainsString($value, $lastDescription);
        }
        $notifs = self::getContainer()->get(NotificationRepository::class)->findBy([
            'signalement' => $signalement,
            'type' => NotificationType::NOUVEAU_SUIVI,
        ]);
        $this->assertCount(3, $notifs);
        $this->assertEmailCount(1);
        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    /**
     * @dataProvider providePayloadFailure
     *
     * @param array<mixed> $payload
     */
    public function testCreateArreteFailed(array $payload): void
    {
        $signalementUuid = '00000000-0000-0000-2022-000000000006';
        $signalement = self::getContainer()->get(SignalementRepository::class)->findOneBy(['uuid' => $signalementUuid]);
        $payload['partenaireUuid'] = $signalement->getAffectations()->first()->getPartner()->getUuid();
        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_arretes_post', [
                'uuid' => $signalementUuid,
            ]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    /**
     * @dataProvider provideDataFailure403
     */
    public function testCreateArretesWithErrors(string $signalementUuid, string $partnerName, string $errorMessage, bool $removeVisiteCompetence = false): void
    {
        $partner = self::getContainer()->get(PartnerRepository::class)->findOneBy(['nom' => $partnerName]);
        $payload = [
            'date' => '2002-03-11',
            'numero' => '2023/DD13/00664',
            'type' => 'Arrêté L.511-11 - Suroccupation',
            'partenaireUuid' => $partner->getUuid(),
        ];

        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_arretes_post', ['uuid' => $signalementUuid]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $this->assertEquals(403, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('Access Denied', $content['message']);
        $this->assertStringContainsString($errorMessage, $content['message']);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    public function provideDataFailure403(): \Generator
    {
        yield 'test create arrete with new affectation' => ['00000000-0000-0000-2022-000000000001', 'Partenaire 13-01', 'L\'affectation doit être au statut EN_COURS'];
        yield 'test create arrete with closed signalement' => ['00000000-0000-0000-2022-000000000003', 'Partenaire 13-01', 'Le signalement n\'est pas actif.'];
        yield 'test create arrete with partner non affecté' => ['00000000-0000-0000-2022-000000000001', 'Partenaire 13-02', ' Le partenaire n\'est pas affecté au signalement.'];
        yield 'test create arrete with partner with no competence visite' => ['00000000-0000-0000-2023-000000000026', 'Partenaire 13-03', 'Le partenaire n\'a pas la compétence visite.', true];
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
                'numeroDossier' => '2023-DD13-00172',
                'type' => 'Arrêté L.511-11 - Suroccupation',
            ],
        ];
        yield 'Payload arrêté with mainlevee' => [
            'arrete_mainlevee',
            [
                'date' => '2025-03-11',
                'numero' => '2023/DD13/00664',
                'numeroDossier' => '2023-DD13-00172',
                'type' => 'Arrêté L.511-11 - Suroccupation',
                'mainLeveeDate' => '2025-03-13',
                'mainLeveeNumero' => '2023-DD13-00172',
            ],
        ];
    }
}
