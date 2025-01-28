<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class BackBailleurControllerTest extends WebTestCase
{
    /**
     * @dataProvider provideParamsBailleurList
     */
    public function testBailleurList(array $params, int $nb): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_bailleur_index');
        $client->request('GET', $route, $params);

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
}
