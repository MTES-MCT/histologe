<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Enum\UserStatus;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class UserControllerTest extends WebTestCase
{
    use SessionHelper;

    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private RouterInterface $router;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->router = self::getContainer()->get(RouterInterface::class);

        $user = $this->userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->client->loginUser($user);
    }

    public function testUserSuccessfullyDisplay(): void
    {
        $route = $this->router->generate('back_user_index');
        $this->client->request('GET', $route);

        $this->assertResponseIsSuccessful();
    }

    public function testDisableUserAccount(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-13-01@signal-logement.fr']);
        $userId = $user->getId();

        $this->client->request('POST', $this->router->generate('back_user_disable'), [
            'user_disable' => ['user' => $userId],
            '_token' => $this->generateCsrfToken($this->client, 'user_disable'),
        ]);

        $user = $this->userRepository->findOneBy(['email' => 'user-13-01@signal-logement.fr']);
        $this->assertEquals(UserStatus::INACTIVE, $user->getStatut());
        $this->assertNull($user->getPassword());
        $this->assertEmailCount(0);
    }

    public function testDisableUserAccountWithCsrfUnvalid(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-13-01@signal-logement.fr']);
        $userId = $user->getId();

        $this->client->request('POST', $this->router->generate('back_user_disable'), [
            'user_disable' => ['user' => $userId],
            '_token' => $this->generateCsrfToken($this->client, 'fauxToken'),
        ]);

        $user = $this->userRepository->findOneBy(['email' => 'user-13-01@signal-logement.fr']);
        $this->assertEquals(UserStatus::ACTIVE, $user->getStatut());
        $this->assertNotNull($user->getPassword());
        $this->assertEmailCount(0);
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertSelectorExists('.fr-alert--error');
    }

    public function testDisableUserAccountWithInactiveUser(): void
    {
        $user = $this->userRepository->findOneBy(['statut' => UserStatus::INACTIVE]);
        $userId = $user->getId();

        $this->client->request('POST', $this->router->generate('back_user_disable'), [
            'user_disable' => ['user' => $userId],
            '_token' => $this->generateCsrfToken($this->client, 'user_disable'),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDisableUserAccountWithNotAdmin(): void
    {
        $agent = $this->userRepository->findOneBy(['email' => 'admin-territoire-13-01@signal-logement.fr']);
        $this->client->loginUser($agent);
        $user = $this->userRepository->findOneBy(['email' => 'user-13-01@signal-logement.fr']);
        $userId = $user->getId();

        $this->client->request('POST', $this->router->generate('back_user_disable'), [
            'user_disable' => ['user' => $userId],
            '_token' => $this->generateCsrfToken($this->client, 'user_disable'),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
