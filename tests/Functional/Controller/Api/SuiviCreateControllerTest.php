<?php

namespace App\Tests\Functional\Controller\Api;

use App\Entity\Suivi;
use App\Entity\User;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Tests\ApiHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\RouterInterface;

class SuiviCreateControllerTest extends WebTestCase
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
     * @dataProvider provideData
     */
    public function testCreateSuivi(string $signalementUuid, bool $notifyUsager): void
    {
        $signalement = self::getContainer()->get(SignalementRepository::class)->findOneBy(['uuid' => $signalementUuid]);
        $firstFile = $signalement?->getFiles()?->first() ?? null;
        $lastFile = $signalement?->getFiles()?->last() ?? null;
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
        $partnerUuid = $affectation->getPartner()->getUuid();
        $payload = [
            'description' => 'lorem ipsum dolor sit <em>amet</em>',
            'notifyUsager' => $notifyUsager,
            'partenaireUuid' => $partnerUuid,
        ];

        $payload['files'] = [$firstFile->getUuid(), $lastFile->getUuid()];
        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_suivis_post', ['uuid' => $signalementUuid]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode($payload)
        );
        if ('0000' !== $signalementUuid) {
            /** @var Suivi $suiviCreated */
            $suiviCreated = $signalement->getSuivis()->last();

            $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
            $this->assertTrue($suiviCreated->isWaitingNotification());
            $this->assertEquals($notifyUsager, $suiviCreated->getIsPublic());
            $this->assertEmailCount(0);

            $crawler = new Crawler($suiviCreated->getDescription());
            $links = $crawler->filter('a.fr-link');
            $this->assertCount(2, $links, 'Il doit y avoir exactement 2 liens dans le contenu HTML.');
        } else {
            $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
        }
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    /**
     * @dataProvider provideDataFailure403
     */
    public function testCreateSuiviWithErrors(string $signalementUuid, string $partnerName, string $errorMessage, bool $removeVisiteCompetence = false): void
    {
        $partner = self::getContainer()->get(PartnerRepository::class)->findOneBy(['nom' => $partnerName]);
        $payload = [
            'description' => 'lorem ipsum dolor sit <em>amet</em>',
            'partenaireUuid' => $partner->getUuid(),
        ];

        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_suivis_post', ['uuid' => $signalementUuid]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode($payload)
        );

        $this->assertEquals(403, $this->client->getResponse()->getStatusCode());
        $content = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertStringContainsString('Access Denied', $content['message']);
        $this->assertStringContainsString($errorMessage, $content['message']);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    public function provideData(): \Generator
    {
        yield 'test create suivi with usager notification' => ['00000000-0000-0000-2022-000000000006', true];
        yield 'test create suivi with no usager notification' => ['00000000-0000-0000-2022-000000000006', false];
        yield 'test create suivi with unknown signalement' => ['0000', false];
    }

    public function provideDataFailure403(): \Generator
    {
        yield 'test create suivi with new affectation' => ['00000000-0000-0000-2022-000000000001', 'Partenaire 13-01', 'L\'affectation doit être au statut EN_COURS'];
        yield 'test create suivi with closed signalement' => ['00000000-0000-0000-2022-000000000003', 'Partenaire 13-01', 'Le signalement n\'est pas actif.'];
        yield 'test create suivi with partner non affecté' => ['00000000-0000-0000-2022-000000000001', 'Partenaire 13-02', ' Le partenaire n\'est pas affecté au signalement.'];
    }
}
