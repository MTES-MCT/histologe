<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class SignalementFileControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private RouterInterface $router;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->router = self::getContainer()->get(RouterInterface::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);

        $user = $this->userRepository->findOneBy(['email' => 'user-13-05@histologe.fr']);
        $this->client->loginUser($user);
    }

    public function testAddFileSignalementNotDeny(): void
    {
        $route = $this->router->generate('back_signalement_add_file', ['uuid' => '00000000-0000-0000-2023-000000000009']);
        $this->client->request('POST', $route);

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('/bo/signalements/00000000-0000-0000-2023-000000000009');
    }

    public function testAddFileSignalementDeny(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-13-05@histologe.fr']);
        $this->client->loginUser($user);

        $route = $this->router->generate('back_signalement_add_file', ['uuid' => '00000000-0000-0000-2023-000000000012']);
        $this->client->request('POST', $route);

        $this->assertResponseStatusCodeSame(403);
    }
}
