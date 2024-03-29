<?php

namespace App\Tests\Functional\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class BackExpiredAccountControllerTest extends WebTestCase
{
    public function testExpiredAccountIndex(): void
    {
        $client = static::createClient();

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_expired_account_index');
        $crawler = $client->request('GET', $route);

        $this->assertEquals(1, $crawler->filter('h2:contains("1 comptes usagers expirés")')->count());
        $this->assertEquals(1, $crawler->filter('h2:contains("1 comptes agents expirés")')->count());
    }
}
