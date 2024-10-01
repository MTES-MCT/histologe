<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class BackUserControllerTest extends WebTestCase
{
    /**
     * @dataProvider provideParamsUserList
     */
    public function testUserList(array $params, int $nb): void
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_user_index');
        $client->request('GET', $route, $params);

        $feature_export_users = static::getContainer()->getParameter('feature_export_users');
        if (!$feature_export_users) {
            $this->markTestSkipped('La fonctionnalité "feature_export_users" est désactivée.');
        }

        if ($nb > 1) {
            $this->assertSelectorTextContains('h2', $nb.' utilisateurs');
        } else {
            $this->assertSelectorTextContains('h2', $nb.' utilisateur');
        }
    }

    public function provideParamsUserList(): iterable
    {
        yield 'Search without params' => [[], 48];
        yield 'Search with queryUser admin' => [['queryUser' => 'admin'], 17];
        yield 'Search with territory 13' => [['territory' => 13], 9];
        yield 'Search with partner 6 and 7' => [['partners' => [6, 7]], 2];
        yield 'Search with status 0' => [['statut' => 0], 8];
        yield 'Search with role ROLE_ADMIN' => [['role' => 'ROLE_ADMIN'], 3];
        yield 'Search with role ROLE_ADMIN and territory 13' => [['role' => 'ROLE_ADMIN', 'territory' => 13], 0];
    }
}
