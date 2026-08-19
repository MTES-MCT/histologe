<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Back;

use App\Repository\UserRepository;
use App\Service\Gouv\Rial\RialService;
use App\Service\Gouv\Topo\TopoService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class RialToolControllerTest extends WebTestCase
{
    public function testIndexAccessDeniedForNonAdmin(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-territoire-13-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $client->request('GET', $router->generate('back_tools_rial'));

        $this->assertResponseStatusCodeSame(403);
    }

    public function testIndexDisplaysFormForAdmin(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $crawler = $client->request('GET', $router->generate('back_tools_rial'));

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Outil RIAL par BAN ID');
        $this->assertCount(1, $crawler->filter('textarea[name="rial_search[banIds]"]'));
        $this->assertSelectorTextContains('a.fr-icon-close-circle-line', 'Réinitialiser les résultats');
    }

    public function testRialSearchWithStandardBanId(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->disableReboot();

        $rialService = $this->createMock(RialService::class);
        $rialService->expects($this->once())
            ->method('searchLocauxByBanId')
            ->with('63214_0136_00005')
            ->willReturn(['123456789']);
        $rialService->expects($this->once())
            ->method('searchLocalByIdFiscal')
            ->with('123456789')
            ->willReturn(['invar' => '123456']);

        static::getContainer()->set(RialService::class, $rialService);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $client->request('GET', $router->generate('back_tools_rial'));

        $crawler = $client->submitForm('Rechercher', [
            'rial_search[banIds]' => '63214_0136_00005',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Résultats (1)');
        $this->assertStringContainsString('63214_0136_00005', $crawler->text());
        $this->assertStringContainsString('123456789', $crawler->text());
        // Pas de formulaire TOPO pour un BAN ID standard
        $this->assertCount(0, $crawler->filter('form[name="topo_search"]'));
    }

    public function testRialSearchWithPseudoCodeBanId(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->disableReboot();

        $rialService = $this->createMock(RialService::class);
        $rialService->expects($this->once())
            ->method('searchLocauxByBanId')
            ->with('63214_f9fzrv_00005')
            ->willReturn([]);

        static::getContainer()->set(RialService::class, $rialService);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $client->request('GET', $router->generate('back_tools_rial'));

        $crawler = $client->submitForm('Rechercher', [
            'rial_search[banIds]' => '63214_f9fzrv_00005',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h3', 'Recherche TOPO DGFiP pour 63214_f9fzrv_00005');
        $this->assertCount(1, $crawler->filter('form[name="topo_search"]'));

        // Vérifier le lien d'aide BAN
        $helpLink = $crawler->filter('form[name="topo_search"] a[href*="adresse.data.gouv.fr"]');
        $this->assertCount(1, $helpLink);
        $this->assertSame('https://adresse.data.gouv.fr/carte-base-adresse-nationale?id=63214_f9fzrv_00005', $helpLink->attr('href'));
        $this->assertSame('adresse.data.gouv.fr - Ouvre une nouvelle fenêtre', $helpLink->attr('title'));
    }

    public function testTopoSearchSubmission(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->disableReboot();

        $rialService = $this->createMock(RialService::class);
        $rialService->expects($this->exactly(2))
            ->method('searchLocauxByBanId')
            ->with('63214_f9fzrv_00005')
            ->willReturn([]);

        $topoService = $this->createMock(TopoService::class);
        $topoService->expects($this->once())
            ->method('searchVoies')
            ->with('63', '214', 'LOUBRETTE')
            ->willReturn([
                [
                    'code_voie' => '0136',
                    'nature_de_voie' => 'RUE',
                    'libelle' => 'LOUBRETTE',
                ],
            ]);

        static::getContainer()->set(RialService::class, $rialService);
        static::getContainer()->set(TopoService::class, $topoService);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $client->request('GET', $router->generate('back_tools_rial'));

        $client->submitForm('Rechercher', [
            'rial_search[banIds]' => '63214_f9fzrv_00005',
        ]);

        $crawler = $client->submitForm('Rechercher dans TOPO DGFiP', [
            'topo_search[libelle]' => 'LOUBRETTE',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h4', 'Résultats TOPO DGFiP pour 63214_f9fzrv_00005');
        $this->assertStringContainsString('0136', $crawler->filter('.fr-table--sm table')->text());
        $this->assertStringContainsString('LOUBRETTE', $crawler->filter('.fr-table--sm table')->text());

        // Vérifier que le message d'aide avec le lien BAN est toujours présent après la soumission
        $helpLink = $crawler->filter('form[name="topo_search"] a[href*="adresse.data.gouv.fr"]');
        $this->assertCount(1, $helpLink);
        $this->assertSame('https://adresse.data.gouv.fr/carte-base-adresse-nationale?id=63214_f9fzrv_00005', $helpLink->attr('href'));
        $this->assertSame('adresse.data.gouv.fr - Ouvre une nouvelle fenêtre', $helpLink->attr('title'));
    }

    public function testMultipleTopoSearchOnlyProcessesSubmittedBanId(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->disableReboot();

        $rialService = $this->createMock(RialService::class);
        $rialService->expects($this->exactly(4))
            ->method('searchLocauxByBanId')
            ->willReturnCallback(function (string $banId) {
                return [];
            });

        $topoService = $this->createMock(TopoService::class);
        $topoService->expects($this->once())
            ->method('searchVoies')
            ->with('63', '214', 'LOUBRETTE')
            ->willReturn([
                [
                    'code_voie' => '0136',
                    'nature_de_voie' => 'RUE',
                    'libelle' => 'LOUBRETTE',
                ],
            ]);

        static::getContainer()->set(RialService::class, $rialService);
        static::getContainer()->set(TopoService::class, $topoService);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $client->request('GET', $router->generate('back_tools_rial'));

        $crawler = $client->submitForm('Rechercher', [
            'rial_search[banIds]' => "63214_f9fzrv_00005\n63214_f9fzrv_00006",
        ]);

        // On a 2 formulaires TOPO affichés sur la page
        $this->assertCount(2, $crawler->filter('form[name="topo_search"]'));

        // On soumet le deuxième formulaire TOPO (pour 63214_f9fzrv_00006)
        $formSecond = $crawler->filter('form[name="topo_search"]')->eq(1)->form([
            'topo_search[libelle]' => 'LOUBRETTE',
        ]);
        $crawler = $client->submit($formSecond);

        $this->assertResponseIsSuccessful();
        // Le résultat TOPO est affiché uniquement pour 63214_f9fzrv_00006
        $this->assertSelectorTextContains('h4', 'Résultats TOPO DGFiP pour 63214_f9fzrv_00006');
        $this->assertStringNotContainsString('Résultats TOPO DGFiP pour 63214_f9fzrv_00005', $crawler->text());
    }
    public function testMultipleTopoSearchPreservesPreviousSearchesAndResults(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->disableReboot();

        $rialService = $this->createMock(RialService::class);
        $rialService->expects($this->exactly(6))
            ->method('searchLocauxByBanId')
            ->willReturnCallback(function (string $banId) {
                return [];
            });

        $topoService = $this->createMock(TopoService::class);
        $topoService->expects($this->exactly(2))
            ->method('searchVoies')
            ->willReturnCallback(function (string $codeDep, string $codeCommune, string $libelle) {
                if ('LOUBRETTE' === $libelle) {
                    return [
                        [
                            'code_voie' => '0136',
                            'nature_de_voie' => 'RUE',
                            'libelle' => 'LOUBRETTE',
                        ],
                    ];
                }

                return [
                    [
                        'code_voie' => '0250',
                        'nature_de_voie' => 'AV',
                        'libelle' => 'MAIRIE',
                    ],
                ];
            });

        static::getContainer()->set(RialService::class, $rialService);
        static::getContainer()->set(TopoService::class, $topoService);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $client->request('GET', $router->generate('back_tools_rial'));

        $crawler = $client->submitForm('Rechercher', [
            'rial_search[banIds]' => "63214_f9fzrv_00005\n63214_f9fzrv_00006",
        ]);

        // Soumission du premier formulaire (pour 63214_f9fzrv_00005)
        $formFirst = $crawler->filter('form[name="topo_search"]')->eq(0)->form([
            'topo_search[libelle]' => 'LOUBRETTE',
        ]);
        $crawler = $client->submit($formFirst);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h4', 'Résultats TOPO DGFiP pour 63214_f9fzrv_00005');
        $this->assertStringContainsString('0136', $crawler->text());
        $this->assertStringNotContainsString('Résultats TOPO DGFiP pour 63214_f9fzrv_00006', $crawler->text());

        // Soumission du deuxième formulaire (pour 63214_f9fzrv_00006)
        $formSecond = $crawler->filter('form[name="topo_search"]')->eq(1)->form([
            'topo_search[libelle]' => 'MAIRIE',
        ]);
        $crawler = $client->submit($formSecond);

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
        $crawler = $client->request('GET', $router->generate('back_tools_rial'));
        $this->assertStringNotContainsString('Résultats TOPO DGFiP', $crawler->text());
    }
}
