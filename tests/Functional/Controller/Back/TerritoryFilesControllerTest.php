<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class TerritoryFilesControllerTest extends WebTestCase
{
    use SessionHelper;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testIndexAsAdmin(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_territory_files_index');

        $this->client->request('GET', $route);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertSelectorExists('form[id=search-territory-files-type-form]');
        $this->assertSelectorExists('select[id=territory]');
    }

    public function testIndexAsNonAdmin(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'user-13-02@signal-logement.fr']);

        $this->client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $route = $router->generate('back_territory_files_index');

        $this->client->request('GET', $route);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->assertSelectorExists('form[id=search-territory-files-type-form]');
        $this->assertSelectorNotExists('select[id=territory]');
    }
}
