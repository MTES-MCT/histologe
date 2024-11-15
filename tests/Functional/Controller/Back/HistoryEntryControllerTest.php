<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class HistoryEntryControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private UserRepository $userRepository;
    private RouterInterface $router;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->router = self::getContainer()->get(RouterInterface::class);
    }

    public function testAccessDeniedForNonAdminTerritoryRole()
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-13-01@histologe.fr']);
        $this->client->loginUser($user);

        $route = $this->router->generate('history_affectation', [
            'id' => 1,
        ]);
        $this->client->request('GET', $route);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testListHistoryAffectationWithValidSignalementId()
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-13-01@histologe.fr']);
        $this->client->loginUser($user);

        $route = $this->router->generate('history_affectation', [
            'id' => 1,
        ]);
        $this->client->request('GET', $route);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $this->assertJson($this->client->getResponse()->getContent());
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('historyEntries', $response);
        $this->assertArrayHasKey('Partenaire 13-01', $response['historyEntries']);
    }

    public function testListHistoryAffectationWithoutSignalementId()
    {
        $user = $this->userRepository->findOneBy(['email' => 'admin-territoire-13-01@histologe.fr']);
        $this->client->loginUser($user);

        $route = $this->router->generate('history_affectation', [
            'id' => 999,
        ]);
        $this->client->request('GET', $route);

        $this->assertResponseStatusCodeSame(403);

        $this->assertJson($this->client->getResponse()->getContent());
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('response', $response);
        $this->assertEquals('error', $response['response']);
    }
}
