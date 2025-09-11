<?php

namespace App\Tests\Functional\Controller\Api;

use App\Entity\Suivi;
use App\Entity\User;
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
    public function testCreateSuivi(string $signalementUuid, bool $notifyUsager, int $nbMailSent): void
    {
        $signalement = self::getContainer()->get(SignalementRepository::class)->findOneBy(['uuid' => $signalementUuid]);
        $firstFile = $signalement?->getFiles()?->first() ?? null;
        $lastFile = $signalement?->getFiles()?->last() ?? null;
        $partnerUuid = $signalement?->getAffectations()->first()->getPartner()->getUuid();
        $payload = [
            'description' => 'lorem ipsum dolor sit <em>amet</em>',
            'notifyUsager' => $notifyUsager,
            'partenaireUuid' => $partnerUuid,
        ];

        if (null !== $firstFile && null !== $lastFile) {
            $payload['files'] = [$firstFile->getUuid(), $lastFile->getUuid()];
        }
        $this->client->request(
            method: 'POST',
            uri: $this->router->generate('api_signalements_suivis_post', ['uuid' => $signalementUuid]),
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload)
        );
        if ('0000' !== $signalementUuid) {
            /** @var Suivi $suiviCreated */
            $suiviCreated = $signalement->getSuivis()->last();

            $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
            $this->assertEmailCount($nbMailSent);

            $crawler = new Crawler($suiviCreated->getDescription());
            $links = $crawler->filter('a.fr-link');
            $this->assertCount(2, $links, 'Il doit y avoir exactement 2 liens dans le contenu HTML.');
        } else {
            $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
        }
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    public function provideData(): \Generator
    {
        yield 'test create suivi with usager notification' => ['00000000-0000-0000-2022-000000000006', true, 2];
        yield 'test create suivi with no usager notification' => ['00000000-0000-0000-2022-000000000006', false, 1, '00000000-0000-0000-2022-000000000006'];
        yield 'test create suivi with unknown signalement' => ['0000', false, 1];
    }
}
