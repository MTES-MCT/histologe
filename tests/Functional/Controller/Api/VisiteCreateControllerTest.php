<?php

namespace App\Tests\Functional\Controller\Api;

use App\Entity\Intervention;
use App\Entity\User;
use App\Repository\SignalementRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\RouterInterface;

class VisiteCreateControllerTest extends WebTestCase
{
    public const string UUID_SIGNALEMENT = '00000000-0000-0000-2022-000000000006';

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

    /** @dataProvider provideData */
    public function testCreateVisite(string $signalementUuid, bool $notifyUsager, int $nbMailSent): void
    {
        $signalement = self::getContainer()->get(SignalementRepository::class)->findOneBy(['uuid' => $signalementUuid]);
        $firstFile = $signalement->getFiles()->first();
        $lastFile = $signalement->getFiles()->last();
        $payload = [
            'date' => '2025-01-01',
            'time' => '12:00',
            'occupantPresent' => true,
            'proprietairePresent' => true,
            'notifyUsager' => $notifyUsager,
            'concludeProcedure' => [
                'LOGEMENT_DECENT',
                'RESPONSABILITE_OCCUPANT_ASSURANTIEL',
            ],
            'details' => 'lorem ipsum dolor sit <em>amet</em>',
            'files' => [
                $firstFile->getUuid(), $lastFile->getUuid(),
            ],
        ];

        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_visite_post', ['uuid' => self::UUID_SIGNALEMENT]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );

        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
        $this->assertEmailCount($nbMailSent);

        /** @var Intervention $lastIntervention */
        $lastIntervention = $signalement->getInterventions()->last();

        $crawler = new Crawler($lastIntervention->getDetails());
        $links = $crawler->filter('a.fr-link');
        $this->assertCount(2, $links, 'Il doit y avoir exactement 2 liens dans le contenu HTML.');
    }

    public function provideData(): \Generator
    {
        yield 'test create visite with usager notification' => [self::UUID_SIGNALEMENT, true, 2];
        yield 'test create visite with no usager notification' => [self::UUID_SIGNALEMENT, false, 1];
    }
}
