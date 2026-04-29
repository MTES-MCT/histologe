<?php

namespace App\Tests\Functional\Controller\Api;

use App\Entity\User;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Tests\ApiHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class VisiteCreateControllerTest extends WebTestCase
{
    use ApiHelper;
    public const string UUID_SIGNALEMENT = '00000000-0000-0000-2023-000000000026';

    private KernelBrowser $client;
    private RouterInterface $router;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $user = static::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy([
            'email' => 'api-01@signal-logement.fr',
        ]);

        $this->router = static::getContainer()->get('router');

        $this->client->loginUser($user, 'api');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideDataForNotification')]
    public function testCreateVisiteWithNotification(string $type, array $payload, int $nbMailSent): void
    {
        $signalement = static::getContainer()->get(SignalementRepository::class)->findOneBy(['uuid' => self::UUID_SIGNALEMENT]);
        $firstFile = $signalement->getFiles()->first();
        $lastFile = $signalement->getFiles()->last();
        if (!$firstFile) {
            $this->fail('No file found for the signalement');
        }
        if (!$lastFile) {
            $this->fail('No file found for the signalement');
        }

        $affectation = $signalement->getAffectations()->first();
        if (!$affectation) {
            $this->fail('No affectation found for the signalement');
        }
        $payload['partenaireUuid'] = $affectation->getPartner()->getUuid();
        if ('visite_confirmed' === $type) {
            $payload['files'] = [$firstFile->getUuid(), $lastFile->getUuid()];
        }
        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_visite_post', ['uuid' => self::UUID_SIGNALEMENT]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertEmailCount($nbMailSent);

        if ('visite_confirmed' === $type) {
            $content = json_decode((string) $this->client->getResponse()->getContent(), true);
            $crawler = new Crawler($content['details']);
            $links = $crawler->filter('a.fr-link');
            $this->assertCount(2, $links, 'Il doit y avoir exactement 2 liens dans le contenu HTML.');
        }
        if ('visite_planned' === $type) {
            $content = json_decode((string) $this->client->getResponse()->getContent(), true);
            $this->assertArrayHasKey('commentBeforeVisite', $content);
            $this->assertEquals('commentaire avant visite ', $content['commentBeforeVisite']);
        }

        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideDataForPendingVisite')]
    public function testCreateVisiteWithPendingVisiteWithErrors(array $payload): void
    {
        $signalementUuid = '00000000-0000-0000-2022-000000000006';
        $signalement = static::getContainer()->get(SignalementRepository::class)->findOneBy(['uuid' => $signalementUuid]);

        $affectation = $signalement->getAffectations()->first();
        if (!$affectation) {
            $this->fail('No affectation found for the signalement');
        }
        $payload['partenaireUuid'] = $affectation->getPartner()->getUuid();
        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_visite_post', ['uuid' => $signalementUuid]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode($payload)
        );
        $intervention = $signalement->getInterventions()->first();
        if (!$intervention) {
            $this->fail('No intervention found for the signalement');
        }

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $message = json_decode((string) $this->client->getResponse()->getContent(), true)['message'];
        $this->assertStringContainsString($intervention->getUuid(), $message);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideDataForPendingVisite')]
    public function testCreateVisiteWithPendingVisite(array $payload): void
    {
        $signalementUuid = '00000000-0000-0000-2022-000000000006';
        $signalement = static::getContainer()->get(SignalementRepository::class)->findOneBy(['uuid' => $signalementUuid]);

        $affectation = $signalement->getAffectations()->first();
        if (!$affectation) {
            $this->fail('No affectation found for the signalement');
        }
        $payload['partenaireUuid'] = $affectation->getPartner()->getUuid();
        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_visite_post', ['uuid' => $signalementUuid]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode($payload)
        );
        $intervention = $signalement->getInterventions()->first();
        if (!$intervention) {
            $this->fail('No intervention found for the signalement');
        }

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $message = json_decode((string) $this->client->getResponse()->getContent(), true)['message'];
        $this->assertStringContainsString($intervention->getUuid(), $message);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideDataErrorPayload')]
    public function testCreateVisiteWithPayloadErrors(array $payload, array $fieldsErrors, string $errorMessage): void
    {
        $signalement = static::getContainer()->get(SignalementRepository::class)->findOneBy(['uuid' => self::UUID_SIGNALEMENT]);

        $affectation = $signalement->getAffectations()->first();
        if (!$affectation) {
            $this->fail('No affectation found for the signalement');
        }
        $payload['partenaireUuid'] = $affectation->getPartner()->getUuid();

        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_visite_post', ['uuid' => self::UUID_SIGNALEMENT]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $errors = json_decode((string) $this->client->getResponse()->getContent(), true)['errors'];
        $this->assertStringContainsString($errorMessage, $errors[0]['message']);
        $errors = array_map(static function ($error) { return $error['property']; }, $errors);
        $this->assertEquals($fieldsErrors, $errors);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideDataFailure403')]
    public function testCreateVisiteWithErrors(string $signalementUuid, string $partnerName, string $errorMessage): void
    {
        $partner = static::getContainer()->get(PartnerRepository::class)->findOneBy(['nom' => $partnerName]);
        $payload = [
            'date' => '2052-03-11',
            'time' => '10:00',
            'partenaireUuid' => $partner->getUuid(),
        ];

        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_visite_post', ['uuid' => $signalementUuid]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode($payload)
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN, (string) $this->client->getResponse()->getContent());
        $content = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('Access Denied', $content['message']);
        $this->assertStringContainsString($errorMessage, $content['message']);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    public static function provideDataFailure403(): \Generator
    {
        yield 'test create visite with new affectation' => ['00000000-0000-0000-2022-000000000001', 'Partenaire 13-01', 'L\'affectation doit être au statut EN_COURS'];
        yield 'test create visite with closed signalement' => ['00000000-0000-0000-2022-000000000003', 'Partenaire 13-01', 'Le signalement n\'est pas actif.'];
        yield 'test create visite with partner non affecté' => ['00000000-0000-0000-2022-000000000001', 'Partenaire 13-02', ' Le partenaire n\'est pas affecté au signalement.'];
        yield 'test create visite with partner with no competence visite' => ['00000000-0000-0000-2023-000000000026', 'Partenaire 13-03', 'Le partenaire n\'a pas la compétence visite.'];
    }

    public static function provideDataForNotification(): \Generator
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
            6,
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
            5,
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

    public static function provideDataForPendingVisite(): \Generator
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

    public static function provideDataErrorPayload(): \Generator
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
