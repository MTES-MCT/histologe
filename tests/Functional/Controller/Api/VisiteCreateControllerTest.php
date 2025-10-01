<?php

namespace App\Tests\Functional\Controller\Api;

use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Tests\ApiHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\RouterInterface;

class VisiteCreateControllerTest extends WebTestCase
{
    use ApiHelper;
    public const string UUID_SIGNALEMENT = '00000000-0000-0000-2023-000000000026';

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
     * @dataProvider provideDataForNotification
     *
     * @param array<mixed> $payload
     */
    public function testCreateVisiteWithNotification(string $type, array $payload, int $nbMailSent): void
    {
        $signalement = self::getContainer()->get(SignalementRepository::class)->findOneBy(['uuid' => self::UUID_SIGNALEMENT]);
        $firstFile = $signalement->getFiles()->first();
        $lastFile = $signalement->getFiles()->last();
        $payload['partenaireUuid'] = $signalement->getAffectations()->first()->getPartner()->getUuid();
        if ('visite_confirmed' === $type) {
            $payload['files'] = [$firstFile->getUuid(), $lastFile->getUuid()];
        }
        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_visite_post', ['uuid' => self::UUID_SIGNALEMENT]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
        $this->assertEmailCount($nbMailSent);

        if ('visite_confirmed' === $type) {
            $content = json_decode($this->client->getResponse()->getContent(), true);
            $crawler = new Crawler($content['details']);
            $links = $crawler->filter('a.fr-link');
            $this->assertCount(2, $links, 'Il doit y avoir exactement 2 liens dans le contenu HTML.');
        }
        if ('visite_planned' === $type) {
            $content = json_decode($this->client->getResponse()->getContent(), true);
            $this->assertArrayHasKey('commentBeforeVisite', $content);
            $this->assertEquals('commentaire avant visite ', $content['commentBeforeVisite']);
        }

        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    /**
     * @dataProvider provideDataForPendingVisite
     *
     * @param array<mixed> $payload
     */
    public function testCreateVisiteWithPendingVisiteWithErrors(array $payload): void
    {
        $signalementUuid = '00000000-0000-0000-2022-000000000006';
        $signalement = self::getContainer()->get(SignalementRepository::class)->findOneBy(['uuid' => $signalementUuid]);
        $payload['partenaireUuid'] = $signalement->getAffectations()->first()->getPartner()->getUuid();
        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_visite_post', ['uuid' => $signalementUuid]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $message = json_decode($this->client->getResponse()->getContent(), true)['message'];
        $this->assertStringContainsString($signalement->getInterventions()->first()->getUuid(), $message);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    /**
     * @dataProvider provideDataForPendingVisite
     *
     * @param array<mixed> $payload
     */
    public function testCreateVisiteWithPendingVisite(array $payload): void
    {
        $signalementUuid = '00000000-0000-0000-2022-000000000006';
        $signalement = self::getContainer()->get(SignalementRepository::class)->findOneBy(['uuid' => $signalementUuid]);
        $payload['partenaireUuid'] = $signalement->getAffectations()->first()->getPartner()->getUuid();
        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_visite_post', ['uuid' => $signalementUuid]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $message = json_decode($this->client->getResponse()->getContent(), true)['message'];
        $this->assertStringContainsString($signalement->getInterventions()->first()->getUuid(), $message);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    /**
     * @dataProvider provideDataErrorPayload
     *
     * @param array<mixed>  $payload
     * @param array<string> $fieldsErrors
     */
    public function testCreateVisiteWithPayloadErrors(array $payload, array $fieldsErrors, string $errorMessage): void
    {
        $signalement = static::getContainer()->get(SignalementRepository::class)->findOneBy(['uuid' => self::UUID_SIGNALEMENT]);
        $payload['partenaireUuid'] = $signalement->getAffectations()->first()->getPartner()->getUuid();

        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_visite_post', ['uuid' => self::UUID_SIGNALEMENT]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $errors = json_decode($this->client->getResponse()->getContent(), true)['errors'];
        $this->assertStringContainsString($errorMessage, $errors[0]['message']);
        $errors = array_map(function ($error) { return $error['property']; }, $errors);
        $this->assertEquals($fieldsErrors, $errors);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    public function provideDataForNotification(): \Generator
    {
        yield 'test create visite confirmed with usager notification' => [
            'visite_confirmed',
            [
                'date' => '2025-01-01',
                'time' => '12:00',
                'visiteEffectuee' => true,
                'occupantPresent' => true,
                'proprietairePresent' => true,
                'notifyUsager' => true,
                'concludeProcedure' => [
                    'LOGEMENT_DECENT',
                    'RESPONSABILITE_OCCUPANT_ASSURANTIEL',
                ],
                'details' => 'lorem ipsum dolor sit <em>amet</em>',
            ],
            5,
        ];
        yield 'test create visite confirmed with no usager notification' => [
            'visite_confirmed',
            [
                'date' => '2025-01-01',
                'time' => '12:00',
                'visiteEffectuee' => true,
                'occupantPresent' => true,
                'proprietairePresent' => true,
                'notifyUsager' => false,
                'concludeProcedure' => [
                    'LOGEMENT_DECENT',
                    'RESPONSABILITE_OCCUPANT_ASSURANTIEL',
                ],
                'details' => 'lorem ipsum dolor sit <em>amet</em>',
            ],
            4,
        ];

        yield 'test create visite planned' => [
            'visite_planned',
            [
                'date' => '2125-01-01',
                'time' => '12:00',
                'commentBeforeVisite' => 'commentaire avant visite <script>alert("XSS")</script>',
            ],
            1,
        ];
    }

    public function provideDataForPendingVisite(): \Generator
    {
        yield 'test create visite planned' => [
            [
                'date' => '2200-06-01',
                'time' => '12:00',
            ],
        ];
        yield 'test create visite confirmed' => [
            [
                'date' => '2024-06-01',
                'time' => '12:00',

                'visiteEffectuee' => true,
                'occupantPresent' => true,
                'proprietairePresent' => true,
                'notifyUsager' => false,
                'details' => 'lorem ipsum dolor sit <em>amet</em>',
                'concludeProcedure' => [
                    'LOGEMENT_DECENT',
                ],
            ],
        ];
    }

    public function provideDataErrorPayload(): \Generator
    {
        yield 'test create visite confirmed with missing data' => [
            [
                'date' => '2020-06-01',
                'time' => '12:00',
            ],
            [
                'visiteEffectuee', 'occupantPresent', 'proprietairePresent', 'notifyUsager', 'concludeProcedure', 'details',
            ],
            'est obligatoire pour une visite effectuée',
        ];
        yield 'test create visite planned with more data than expected' => [
            [
                'date' => '2126-06-01',
                'time' => '12:00',
                'occupantPresent' => true,
                'proprietairePresent' => true,
                'notifyUsager' => false,
                'details' => 'lorem ipsum dolor sit <em>amet</em>',
                'concludeProcedure' => [
                    'LOGEMENT_DECENT',
                ],
            ],
            [
                'occupantPresent', 'proprietairePresent', 'notifyUsager', 'concludeProcedure', 'details',
            ],
            'ne peut être renseigné que si la visite a été effectuée',
        ];
    }
}
