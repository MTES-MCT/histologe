<?php

namespace App\Tests\Functional\Controller;

use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class AddressesHistoryControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private RouterInterface $router;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $_ENV['FEATURE_HISTO_ADDRESS'] = '1';
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->router = static::getContainer()->get(RouterInterface::class);

        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-13-01@signal-logement.fr']);
        $this->client->loginUser($user);
    }

    protected function tearDown(): void
    {
        $_ENV['FEATURE_HISTO_ADDRESS'] = '0';
        parent::tearDown();
    }

    public function testExportCsv(): void
    {
        $route = $this->router->generate('back_addresses_history_export', ['format' => 'csv']);
        $this->client->request('GET', $route);

        $this->assertResponseIsSuccessful();
        $response = $this->client->getResponse();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="adresses_', (string) $response->headers->get('Content-Disposition'));

        $content = $this->client->getInternalResponse()->getContent();
        $this->assertStringContainsString('Adresse;"Code postal";Commune;"Nombre de dossiers";"Références des dossiers"', $content);
        $this->assertStringContainsString('Bailleur;Syndicat;"Nature du parc"', $content);
    }

    public function testExportXlsx(): void
    {
        $route = $this->router->generate('back_addresses_history_export', ['format' => 'xlsx']);
        $this->client->request('GET', $route);

        $this->assertResponseIsSuccessful();
        $response = $this->client->getResponse();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $response->headers->get('Content-Type')
        );
        $this->assertStringContainsString('attachment; filename="adresses_', (string) $response->headers->get('Content-Disposition'));
        $this->assertNotEmpty($this->client->getInternalResponse()->getContent());
    }

    public function testExportDefaultsToCsvWithInvalidFormat(): void
    {
        $route = $this->router->generate('back_addresses_history_export', ['format' => 'invalid']);
        $this->client->request('GET', $route);

        $this->assertResponseIsSuccessful();
        $response = $this->client->getResponse();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
    }

    public function testExportIsRestrictedToAdminTerritory(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-13-01@signal-logement.fr']);
        $this->client->loginUser($user);

        $route = $this->router->generate('back_addresses_history_export');
        $this->client->request('GET', $route);

        $this->assertResponseStatusCodeSame(403);
    }

    #[DataProvider('provideSignalementUuidOnSameAddress')]
    public function testSignalementViewLinksToAddressHistory(string $uuid): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-30@signal-logement.fr']);
        $this->client->loginUser($user);

        $route = $this->router->generate('back_signalement_view', ['uuid' => $uuid]);
        $crawler = $this->client->request('GET', $route);
        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('#header-address', '1 autre dossier et 3 arrêtés trouvés à cette adresse');

        $link = $crawler->filter('#header-address a')->reduce(
            static fn ($node) => str_contains($node->text(), "Voir l'historique")
        );
        $this->assertCount(1, $link);

        $href = (string) $link->attr('href');
        $this->assertSame($this->router->generate('back_addresses_history_index'), parse_url($href, \PHP_URL_PATH));

        $query = (string) parse_url($href, \PHP_URL_QUERY);
        $listRoute = $this->router->generate('back_addresses_history_list_json').'?'.$query;
        $this->client->request('GET', $listRoute);
        $this->assertResponseIsSuccessful();

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $response['list']);
        $this->assertCount(2, $response['list'][0]['signalements']);
        $this->assertCount(3, $response['list'][0]['arretes']);
    }

    public static function provideSignalementUuidOnSameAddress(): \Generator
    {
        yield 'Signalement 2025-TEMPORAIRE' => ['00000000-0000-0000-2025-000000000011'];
        yield 'Signalement 2026-02' => ['00000000-0000-0000-2026-000000000002'];
    }
}
