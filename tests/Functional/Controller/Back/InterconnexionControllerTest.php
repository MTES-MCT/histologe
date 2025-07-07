<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class InterconnexionControllerTest extends WebTestCase
{
    /**
     * @dataProvider provideParamsInterconnexionList
     *
     * @param array<mixed> $params
     */
    public function testInterconnexionList(array $params): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_interconnexion_index');
        $client->request('GET', $route, $params);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h2#desc-table');
    }

    public function provideParamsInterconnexionList(): \Generator
    {
        yield 'Search without params' => [[]];
        yield 'Search with status success' => [['status' => 'success']];
        yield 'Search with status failed' => [['status' => 'failed']];
    }
}
