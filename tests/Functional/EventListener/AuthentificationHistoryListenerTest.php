<?php

namespace App\Tests\Functional\EventListener;

use App\EventListener\AuthentificationHistoryListener;
use App\Manager\HistoryEntryManager;
use App\Repository\HistoryEntryRepository;
use App\Repository\UserRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthentificationHistoryListenerTest extends WebTestCase
{
    public const USER_ADMIN_TERRITORY_13 = 'admin-territoire-13-01@histologe.fr';
    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private HistoryEntryRepository $historyEntryRepository;
    private UrlGeneratorInterface $urlGenerator;
    private MockObject|LoggerInterface $logger;
    private MockObject|TokenInterface $token;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->urlGenerator = self::getContainer()->get(UrlGeneratorInterface::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->historyEntryRepository = static::getContainer()->get(HistoryEntryRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->token = $this->createMock(TokenInterface::class);
    }

    public function testInteractiveLogin(): void
    {
        $this->client->request('GET', '/connexion');

        $this->client->submitForm('Connexion', [
            'email' => self::USER_ADMIN_TERRITORY_13,
            'password' => 'histologe',
        ]);

        $historyEntries = $this->historyEntryRepository->findAll();
        $this->assertNotEmpty($historyEntries);
    }

    public function testOnSchebTwoFactorAuthenticationSuccess(): void
    {
        $historyEntryManager = self::getContainer()->get(HistoryEntryManager::class);
        $authentificationHistoryListener = new AuthentificationHistoryListener(
            $historyEntryManager,
            $this->logger
        );

        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);

        $this->token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $route = $this->urlGenerator->generate('2fa_login_check');

        $event = new TwoFactorAuthenticationEvent(
            Request::create(
                $route,
                Request::METHOD_POST,
                ['_auth_code' => 1234]
            ),
            $this->token
        );

        $authentificationHistoryListener->onSchebTwoFactorAuthenticationSuccess($event);
        $historyEntries = $this->historyEntryRepository->findAll();
        $this->assertNotEmpty($historyEntries);
    }
}
