<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardControllerTest extends WebTestCase
{
    private const USER_ADMIN = 'admin-01@signal-logement.fr';

    public function testIndexWithFeatureNewDashboard(): void
    {
        $client = static::createClient();
        $router = self::getContainer()->get('router');
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => self::USER_ADMIN]);
        $client->loginUser($user);
        $url = $router->generate('back_dashboard');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('div');
        $this->assertSelectorTextContains('title', 'Tableau de bord');
    }
}
