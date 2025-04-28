<?php

namespace App\Tests\Functional\Controller\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Gouv\ProConnect\Model\ProConnectUser;
use App\Service\Gouv\ProConnect\ProConnectAuthentication;
use App\Service\Gouv\ProConnect\ProConnectContext;
use App\Tests\SessionHelper;
use App\Tests\UserHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ProConnectControllerTest extends WebTestCase
{
    use SessionHelper;
    use UserHelper;

    /**
     * @throws \Exception
     */
    public function testSuccessfulCallback(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $session = $this->getSession($client);
        $session->set('proconnect_state', 'fake_state');
        $session->set('proconnect_nonce', 'fake_nonce');
        $session->save();

        $proConnectUser = new ProConnectUser([
            'uid' => '1234',
            'sub' => '1234',
            'email' => 'test@proconnect.fr',
            'given_name' => 'Jean',
            'family_name' => 'Dupont',
            'usual_name' => 'JD',
        ]);

        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $container->get('doctrine.orm.entity_manager');
        $user = (new User())
            ->setEmail($proConnectUser->email)
            ->setStatut(User::STATUS_ACTIVE);
        $em->persist($user);

        $userRepositoryMock = $this->createMock(UserRepository::class);
        $userRepositoryMock
            ->method('findByProConnectUser')
            ->willReturn($user);
        static::getContainer()->set(UserRepository::class, $userRepositoryMock);

        $mockAuth = $this->createMock(ProConnectAuthentication::class);
        $mockAuth
            ->method('authenticateFromCallback')
            ->willReturn($proConnectUser);
        $container->set(ProConnectAuthentication::class, $mockAuth);

        $mockContext = $this->createMock(ProConnectContext::class);
        $mockContext->method('clearSession');
        $container->set(ProConnectContext::class, $mockContext);

        $client->request('GET', '/proconnect/login-callback?code=fake-code&state=fake_state');

        $this->assertResponseRedirects('/bo/');
        $client->followRedirect();

        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = static::getContainer()->get('security.token_storage');
        $token = $tokenStorage->getToken();

        /** @var User $user */
        $user = $token->getUser();
        $this->assertSame('1234', $user->getProConnectUserId());
        $this->assertSame('test@proconnect.fr', $user->getEmail());
        $this->assertSame(date('Y-m-d'), $user->getLastLoginAtStr('Y-m-d'));
    }

    public function testLoginWithProConnect(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $mockAuth = $this->createMock(ProConnectAuthentication::class);
        $mockAuth
            ->method('getAuthorizationUrl')
            ->willReturn('https://auth.proconnect.fake/authorize');

        $container->set(ProConnectAuthentication::class, $mockAuth);

        $client->request('POST', '/proconnect/login');

        $this->assertResponseRedirects('https://auth.proconnect.fake/authorize');
    }

    public function testLoginWithProConnectFailsGracefully(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        $mockAuth = $this->createMock(ProConnectAuthentication::class);
        $mockAuth
            ->method('getAuthorizationUrl')
            ->willThrowException(new \RuntimeException('Erreur fake'));

        $container->set(ProConnectAuthentication::class, $mockAuth);

        $client->request('POST', '/proconnect/login');

        $this->assertResponseRedirects('/connexion');
        $client->followRedirect();
    }
}
