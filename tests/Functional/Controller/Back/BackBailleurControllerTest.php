<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\BailleurRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class BackBailleurControllerTest extends WebTestCase
{
    use SessionHelper;
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->client->loginUser($user);
    }

    /**
     * @dataProvider provideParamsBailleurList
     */
    public function testBailleurList(array $params, int $nb): void
    {
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_bailleur_index');
        $this->client->request('GET', $route, $params);

        if ($nb > 1) {
            $this->assertSelectorTextContains('h2', $nb.' bailleurs');
        } else {
            $this->assertSelectorTextContains('h2', $nb.' bailleur');
        }
    }

    public function provideParamsBailleurList(): iterable
    {
        yield 'Search without params' => [[], 67];
        yield 'Search with queryName Habitat' => [['queryName' => 'Habitat'], 28];
        yield 'Search with territory 13' => [['territory' => 13], 47];
    }

    public function testBailleurEdit(): void
    {
        /** @var BailleurRepository $bailleurRepository */
        $bailleurRepository = static::getContainer()->get(BailleurRepository::class);
        $bailleur = $bailleurRepository->findOneBy(['name' => '13 HABITAT']);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_bailleur_edit', ['bailleur' => $bailleur->getId()]);

        $csrfToken = $this->generateCsrfToken($this->client, 'bailleur_type');
        $this->client->request('POST', $route, [
            '_token' => $csrfToken,
            'name' => 'Bailleur Test',
        ]);

        $this->assertEquals('Bailleur Test', $bailleur->getName());
    }

    public function testBailleurDeleteKO(): void
    {
        /** @var BailleurRepository $bailleurRepository */
        $bailleurRepository = static::getContainer()->get(BailleurRepository::class);
        $bailleur = $bailleurRepository->findOneBy(['name' => '13 HABITAT']);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_bailleur_delete', ['bailleur' => $bailleur->getId()]);

        $csrfToken = $this->generateCsrfToken($this->client, 'bailleur_delete');
        $this->client->request('GET', $route, ['_token' => $csrfToken]);

        $bailleur = $bailleurRepository->findOneBy(['id' => $bailleur->getId()]);
        $this->assertNotNull($bailleur);
        $this->assertResponseRedirects();
    }

    public function testBailleurDeleteOK(): void
    {
        /** @var BailleurRepository $bailleurRepository */
        $bailleurRepository = static::getContainer()->get(BailleurRepository::class);
        $bailleur = $bailleurRepository->findOneBy(['name' => '3F SUD SA HLM']);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_bailleur_delete', ['bailleur' => $bailleur->getId()]);

        $csrfToken = $this->generateCsrfToken($this->client, 'bailleur_delete');
        $this->client->request('GET', $route, ['_token' => $csrfToken]);

        $bailleur = $bailleurRepository->findOneBy(['id' => $bailleur->getId()]);
        $this->assertNull($bailleur);
    }
}
