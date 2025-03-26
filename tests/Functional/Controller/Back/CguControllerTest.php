<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class CguControllerTest extends WebTestCase
{
    use SessionHelper;

    public function testValidateCGU()
    {
        $client = static::createClient();
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $user = $userRepository->findOneBy(['email' => 'user-13-01@signal-logement.fr']);
        $client->loginUser($user);

        $route = $router->generate('cgu_bo_confirm');
        $payload = [
            '_token' => $this->generateCsrfToken($client, 'cgu_bo_confirm'),
        ];

        $client->request(
            method: 'POST',
            uri: $route,
            content: json_encode($payload)
        );

        $this->assertEquals('05/06/2024', $user->getCguVersionChecked());
        $this->assertResponseIsSuccessful();
    }
}
