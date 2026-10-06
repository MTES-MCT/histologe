<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Back;

use App\Entity\Territory;
use App\Entity\User;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class MetabaseControllerTest extends WebTestCase
{
    private const string USER_ADMIN = 'admin-01@signal-logement.fr';
    private const string USER_MULTI_TERRITOIRE = 'user-partenaire-multi-ter-34-30@signal-logement.fr';
    private const string USER_SINGLE_TERRITOIRE = 'user-13-01@signal-logement.fr';

    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private TerritoryRepository $territoryRepository;
    private RouterInterface $router;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->territoryRepository = static::getContainer()->get(TerritoryRepository::class);
        $this->router = static::getContainer()->get(RouterInterface::class);
    }


    public function testMetabaseStatsIndexAsAdminWithoutFilter(): void
    {
        /** @var User $adminUser */
        $adminUser = $this->userRepository->findOneBy(['email' => self::USER_ADMIN]);
        $this->client->loginUser($adminUser);

        $url = $this->router->generate('back_beta_statistiques');
        $crawler = $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Statistiques (version beta)');
        $this->assertSelectorExists('iframe[data-type="metabase"]');
        $this->assertSelectorExists('iframe[title="Statistiques des signalements"]');

        $iframe = $crawler->filter('iframe[data-type="metabase"]');
        $src = $iframe->attr('src');
        $this->assertNotNull($src);
        $this->assertStringContainsString('/embed/dashboard/', $src);

        $this->assertSelectorExists('form#metabase-dashboard-filter-form');
        $this->assertSelectorExists('select[name="territory"]');
    }

    public function testMetabaseStatsIndexAsAdminWithTerritoryFilter(): void
    {
        /** @var User $adminUser */
        $adminUser = $this->userRepository->findOneBy(['email' => self::USER_ADMIN]);
        $this->client->loginUser($adminUser);

        /** @var Territory $territory */
        $territory = $this->territoryRepository->findOneBy(['zip' => '13']);

        $url = $this->router->generate('back_beta_statistiques', [
            'territory' => $territory->getId(),
        ]);
        $crawler = $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $expectedTitle = sprintf('Statistiques des signalements pour le territoire %s', $territory->getZipAndName());
        $this->assertSelectorExists(sprintf('iframe[title="%s"]', $expectedTitle));

        $iframe = $crawler->filter('iframe[data-type="metabase"]');
        $src = $iframe->attr('src');
        $this->assertNotNull($src);
        $this->assertStringContainsString('/embed/dashboard/', $src);
    }

    public function testMetabaseStatsIndexAsMultiTerritoryUserWithoutFilter(): void
    {
        /** @var User $user */
        $user = $this->userRepository->findOneBy(['email' => self::USER_MULTI_TERRITOIRE]);
        $this->client->loginUser($user);

        $url = $this->router->generate('back_beta_statistiques');
        $crawler = $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('iframe[title="Statistiques des signalements"]');
        $this->assertSelectorExists('form#metabase-dashboard-filter-form');

        $iframe = $crawler->filter('iframe[data-type="metabase"]');
        $src = $iframe->attr('src');
        $this->assertNotNull($src);
        $this->assertStringContainsString('/embed/dashboard/', $src);
    }

    public function testMetabaseStatsIndexAsMultiTerritoryUserWithAuthorizedFilter(): void
    {
        /** @var User $user */
        $user = $this->userRepository->findOneBy(['email' => self::USER_MULTI_TERRITOIRE]);
        $this->client->loginUser($user);

        $authorizedTerritories = $user->getPartnersTerritories();
        $this->assertNotEmpty($authorizedTerritories);
        /** @var Territory $territory */
        $territory = reset($authorizedTerritories);

        $url = $this->router->generate('back_beta_statistiques', [
            'territory' => $territory->getId(),
        ]);
        $crawler = $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $expectedTitle = sprintf('Statistiques des signalements pour le territoire %s', $territory->getZipAndName());
        $this->assertSelectorExists(sprintf('iframe[title="%s"]', $expectedTitle));

        $iframe = $crawler->filter('iframe[data-type="metabase"]');
        $src = $iframe->attr('src');
        $this->assertNotNull($src);
        $this->assertStringContainsString('/embed/dashboard/', $src);
    }

    public function testMetabaseStatsIndexAsSingleTerritoryUser(): void
    {
        /** @var User $user */
        $user = $this->userRepository->findOneBy(['email' => self::USER_SINGLE_TERRITOIRE]);
        $this->client->loginUser($user);

        $url = $this->router->generate('back_beta_statistiques');
        $crawler = $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('iframe[title="Statistiques des signalements"]');
        $this->assertSelectorNotExists('form#metabase-dashboard-filter-form');

        $iframe = $crawler->filter('iframe[data-type="metabase"]');
        $src = $iframe->attr('src');
        $this->assertNotNull($src);
        $this->assertStringContainsString('/embed/dashboard/', $src);
    }

    public function testMetabaseStatsIndexWithInvalidFilterFallsBack(): void
    {
        /** @var User $adminUser */
        $adminUser = $this->userRepository->findOneBy(['email' => self::USER_ADMIN]);
        $this->client->loginUser($adminUser);

        $url = $this->router->generate('back_beta_statistiques', [
            'territory' => 9999999,
        ]);
        $crawler = $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('iframe[title="Statistiques des signalements"]');

        $iframe = $crawler->filter('iframe[data-type="metabase"]');
        $src = $iframe->attr('src');
        $this->assertNotNull($src);
        $this->assertStringContainsString('/embed/dashboard/', $src);
    }
}
