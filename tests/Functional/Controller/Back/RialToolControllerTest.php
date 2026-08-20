<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Back;

use App\Repository\UserRepository;
use App\Service\Gouv\Rial\RialService;
use App\Service\Gouv\Topo\TopoService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Routing\RouterInterface;

class RialToolControllerTest extends WebTestCase
{
    private const string USER_ADMIN = 'admin-01@signal-logement.fr';
    private const string USER_NON_ADMIN = 'admin-territoire-13-01@signal-logement.fr';
    private const string PSEUDO_BAN_ID = '63214_f9fzrv_00005';
    private const string STANDARD_BAN_ID = '63214_0136_00005';

    private ?KernelBrowser $client = null;
    private RouterInterface $router;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->router = static::getContainer()->get(RouterInterface::class);
    }

    public function testIndexAccessDeniedForNonAdmin(): void
    {
        $this->loginAndGet(self::USER_NON_ADMIN);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testIndexDisplaysFormForAdmin(): void
    {
        $crawler = $this->loginAndGet();

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Outil RIAL par BAN ID');
        $this->assertCount(1, $crawler->filter('textarea[name="rial_search[banIds]"]'));
        $this->assertSelectorTextContains('a.fr-icon-close-circle-line', 'Réinitialiser les résultats');
    }

    public function testRialSearchWithStandardBanId(): void
    {
        $this->client->disableReboot();

        $rialService = $this->createMock(RialService::class);
        $rialService->expects($this->once())
            ->method('searchLocauxByBanId')
            ->with(self::STANDARD_BAN_ID)
            ->willReturn(['123456789']);
        $rialService->expects($this->once())
            ->method('searchLocalByIdFiscal')
            ->with('123456789')
            ->willReturn(['invar' => '123456']);

        static::getContainer()->set(RialService::class, $rialService);

        $this->loginAndGet();
        $crawler = $this->submitRialSearch(self::STANDARD_BAN_ID);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Résultats (1)');
        $this->assertStringContainsString(self::STANDARD_BAN_ID, $crawler->text());
        $this->assertStringContainsString('123456789', $crawler->text());
        // Pas de formulaire TOPO pour un BAN ID standard
        $this->assertCount(0, $crawler->filter('form[name="topo_search"]'));
    }

    public function testRialSearchWithPseudoCodeBanId(): void
    {
        $this->client->disableReboot();

        $rialService = $this->createMock(RialService::class);
        $rialService->expects($this->once())
            ->method('searchLocauxByBanId')
            ->with(self::PSEUDO_BAN_ID)
            ->willReturn([]);

        static::getContainer()->set(RialService::class, $rialService);

        $this->loginAndGet();
        $crawler = $this->submitRialSearch(self::PSEUDO_BAN_ID);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'Recherche TOPO DGFiP pour '.self::PSEUDO_BAN_ID);
        $this->assertCount(1, $crawler->filter('form[name="topo_search"]'));

        // Vérifier le lien d'aide BAN
        $this->assertBanHelpLink($crawler, self::PSEUDO_BAN_ID);
    }

    public function testTopoSearchSubmission(): void
    {
        $this->client->disableReboot();

        $rialService = $this->createMock(RialService::class);
        $rialService->expects($this->exactly(2))
            ->method('searchLocauxByBanId')
            ->with(self::PSEUDO_BAN_ID)
            ->willReturn([]);

        $topoService = $this->createMock(TopoService::class);
        $topoService->expects($this->once())
            ->method('searchVoies')
            ->with('63', '214', 'LOUBRETTE')
            ->willReturn($this->createLoubretteVoieResult());

        static::getContainer()->set(RialService::class, $rialService);
        static::getContainer()->set(TopoService::class, $topoService);

        $this->loginAndGet();
        $crawler = $this->submitRialSearch(self::PSEUDO_BAN_ID);
        $crawler = $this->submitTopoForm($crawler, 0, 'LOUBRETTE');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h4', 'Résultats TOPO DGFiP pour '.self::PSEUDO_BAN_ID);
        $this->assertStringContainsString('0136', $crawler->filter('.fr-table--sm table')->text());
        $this->assertStringContainsString('LOUBRETTE', $crawler->filter('.fr-table--sm table')->text());

        // Vérifier que le message d'aide avec le lien BAN est toujours présent après la soumission
        $this->assertBanHelpLink($crawler, self::PSEUDO_BAN_ID);
    }

    public function testMultipleTopoSearchOnlyProcessesSubmittedBanId(): void
    {
        $this->client->disableReboot();

        $rialService = $this->createMock(RialService::class);
        $rialService->expects($this->exactly(4))
            ->method('searchLocauxByBanId')
            ->willReturnCallback(static fn (string $banId) => []);

        $topoService = $this->createMock(TopoService::class);
        $topoService->expects($this->once())
            ->method('searchVoies')
            ->with('63', '214', 'LOUBRETTE')
            ->willReturn($this->createLoubretteVoieResult());

        static::getContainer()->set(RialService::class, $rialService);
        static::getContainer()->set(TopoService::class, $topoService);

        $this->loginAndGet();
        $crawler = $this->submitRialSearch("63214_f9fzrv_00005\n63214_f9fzrv_00006");

        // On a 2 formulaires TOPO affichés sur la page
        $this->assertCount(2, $crawler->filter('form[name="topo_search"]'));

        // On soumet le deuxième formulaire TOPO (pour 63214_f9fzrv_00006)
        $crawler = $this->submitTopoForm($crawler, 1, 'LOUBRETTE');

        $this->assertResponseIsSuccessful();
        // Le résultat TOPO est affiché uniquement pour 63214_f9fzrv_00006
        $this->assertSelectorTextContains('h4', 'Résultats TOPO DGFiP pour 63214_f9fzrv_00006');
        $this->assertStringNotContainsString('Résultats TOPO DGFiP pour 63214_f9fzrv_00005', $crawler->text());
    }

    public function testMultipleTopoSearchPreservesPreviousSearchesAndResults(): void
    {
        $this->client->disableReboot();

        $rialService = $this->createMock(RialService::class);
        $rialService->expects($this->exactly(6))
            ->method('searchLocauxByBanId')
            ->willReturnCallback(static fn (string $banId) => []);

        $topoService = $this->createMock(TopoService::class);
        $topoService->expects($this->exactly(2))
            ->method('searchVoies')
            ->willReturnCallback(fn (string $codeDep, string $codeCommune, string $libelle) => 'LOUBRETTE' === $libelle
                ? $this->createLoubretteVoieResult()
                : [
                    [
                        'code_voie' => '0250',
                        'nature_de_voie' => 'AV',
                        'libelle' => 'MAIRIE',
                    ],
                ]
            );

        static::getContainer()->set(RialService::class, $rialService);
        static::getContainer()->set(TopoService::class, $topoService);

        $this->loginAndGet();
        $crawler = $this->submitRialSearch("63214_f9fzrv_00005\n63214_f9fzrv_00006");

        // Soumission du premier formulaire (pour 63214_f9fzrv_00005)
        $crawler = $this->submitTopoForm($crawler, 0, 'LOUBRETTE');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h4', 'Résultats TOPO DGFiP pour 63214_f9fzrv_00005');
        $this->assertStringContainsString('0136', $crawler->text());
        $this->assertStringNotContainsString('Résultats TOPO DGFiP pour 63214_f9fzrv_00006', $crawler->text());

        // Soumission du deuxième formulaire (pour 63214_f9fzrv_00006)
        $crawler = $this->submitTopoForm($crawler, 1, 'MAIRIE');

        $this->assertResponseIsSuccessful();
        // Les deux résultats TOPO doivent être présents
        $this->assertStringContainsString('Résultats TOPO DGFiP pour 63214_f9fzrv_00005', $crawler->text());
        $this->assertStringContainsString('0136', $crawler->text());
        $this->assertStringContainsString('Résultats TOPO DGFiP pour 63214_f9fzrv_00006', $crawler->text());
        $this->assertStringContainsString('0250', $crawler->text());

        // Les champs de saisie conservent leurs libellés
        $firstInput = $crawler->filter('form[name="topo_search"] input[name="topo_search[libelle]"]')->eq(0);
        $secondInput = $crawler->filter('form[name="topo_search"] input[name="topo_search[libelle]"]')->eq(1);
        $this->assertSame('LOUBRETTE', $firstInput->attr('value'));
        $this->assertSame('MAIRIE', $secondInput->attr('value'));

        // Réinitialisation via un accès GET
        $crawler = $this->client->request('GET', $this->router->generate('back_tools_rial'));
        $this->assertStringNotContainsString('Résultats TOPO DGFiP', $crawler->text());
    }

    private function loginAndGet(string $email = self::USER_ADMIN): Crawler
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        $this->client->loginUser($user);

        return $this->client->request('GET', $this->router->generate('back_tools_rial'));
    }

    private function submitRialSearch(string $banIds): Crawler
    {
        return $this->client->submitForm('Rechercher', [
            'rial_search[banIds]' => $banIds,
        ]);
    }

    private function submitTopoForm(Crawler $crawler, int $index, string $libelle): Crawler
    {
        $form = $crawler->filter('form[name="topo_search"]')->eq($index)->form([
            'topo_search[libelle]' => $libelle,
        ]);

        return $this->client->submit($form);
    }

    private function assertBanHelpLink(Crawler $crawler, string $banId): void
    {
        $helpLink = $crawler->filter('form[name="topo_search"] a[href*="adresse.data.gouv.fr"]');
        $this->assertCount(1, $helpLink);
        $this->assertSame('https://adresse.data.gouv.fr/carte-base-adresse-nationale?id='.$banId, $helpLink->attr('href'));
        $this->assertSame('adresse.data.gouv.fr - Ouvre une nouvelle fenêtre', $helpLink->attr('title'));
    }

    /**
     * @return array<array{code_voie: string, nature_de_voie: string, libelle: string}>
     */
    private function createLoubretteVoieResult(): array
    {
        return [
            [
                'code_voie' => '0136',
                'nature_de_voie' => 'RUE',
                'libelle' => 'LOUBRETTE',
            ],
        ];
    }
}
