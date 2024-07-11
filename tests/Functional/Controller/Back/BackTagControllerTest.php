<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class BackTagControllerTest extends WebTestCase
{
    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private RouterInterface $router;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->router = self::getContainer()->get(RouterInterface::class);

        $user = $this->userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $this->client->loginUser($user);
    }

    public function testCreateTagSuccess(): void
    {
        $route = $this->router->generate('back_tag_create', ['uuid' => '00000000-0000-0000-2023-000000000006']);
        $this->client->request('POST', $route, ['new-tag-label' => 'test']);

        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('success', $this->client->getResponse()->getContent());
    }

    public function testCreateTagError(): void
    {
        $route = $this->router->generate('back_tag_create', ['uuid' => '00000000-0000-0000-2023-000000000006']);
        $this->client->request('POST', $route, ['new-tag-label' => 't']);

        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('Le tag doit contenir au moins 2 caract\u00e8res', $this->client->getResponse()->getContent());
    }
}
