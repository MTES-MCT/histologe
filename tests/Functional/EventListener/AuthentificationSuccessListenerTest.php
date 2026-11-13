<?php

namespace App\Tests\Functional\EventListener;

use App\EventListener\AuthentificationSuccessListener;
use App\Manager\HistoryEntryManager;
use App\Manager\SuiviManager;
use App\Repository\HistoryEntryRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Service\History\HistoryEntryBuffer;
use App\Service\Security\NewLoginNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Event\TwoFactorAuthenticationEvent;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthentificationSuccessListenerTest extends WebTestCase
{
    public const USER_ADMIN_TERRITORY_13 = 'admin-territoire-13-01@signal-logement.fr';
    private ?KernelBrowser $client = null;
    private HistoryEntryRepository $historyEntryRepository;
    private UrlGeneratorInterface $urlGenerator;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->urlGenerator = static::getContainer()->get(UrlGeneratorInterface::class);
        $this->historyEntryRepository = static::getContainer()->get(HistoryEntryRepository::class);
        $this->logger = $this->createStub(LoggerInterface::class);
    }

    public function testInteractiveLogin(): void
    {
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => self::USER_ADMIN_TERRITORY_13]);
        $this->assertNull($user->getLastLoginAt(), 'lastLoginAt doit être null avant connexion');
        $this->client->request('GET', '/connexion');

        $this->client->submitForm('Se connecter', [
            'email' => self::USER_ADMIN_TERRITORY_13,
            'password' => 'signallogement',
        ]);

        $userAfterLogin = $userRepository->find($user->getId());
        $this->assertNotNull($userAfterLogin->getLastLoginAt(), 'lastLoginAt doit être mis à jour après connexion');
        $this->assertNotNull($userAfterLogin->getLastLoginFingerprint(), 'l\'empreinte de connexion doit être enregistrée pour tous les rôles');
        $this->assertEmailCount(0, message: 'pas de notification de nouvelle connexion sans empreinte précédente');

        $now = new \DateTimeImmutable();
        $diffSeconds = abs($now->getTimestamp() - $userAfterLogin->getLastLoginAt()->getTimestamp());
        $this->assertLessThan(5, $diffSeconds, 'lastLoginAt doit être très récent (moins de 5 secondes)');
        $historyEntries = $this->historyEntryRepository->findAll();
        $this->assertNotEmpty($historyEntries);
    }

    public function testOnSchebTwoFactorAuthenticationSuccess(): void
    {
        $historyEntryManager = static::getContainer()->get(HistoryEntryManager::class);
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $authentificationSuccessListener = new AuthentificationSuccessListener(
            $historyEntryManager,
            $this->logger,
            $signalementRepository,
            $entityManager,
            '1',
            static::getContainer()->get(HistoryEntryBuffer::class),
            static::getContainer()->get(SuiviManager::class),
            static::getContainer()->get(NewLoginNotifier::class),
        );

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);

        $this->assertNull($user->getLastLoginAt(), 'lastLoginAt doit être null avant 2FA');
        $user->setLastLoginFingerprint(hash('sha256', 'environnement précédent'));
        $entityManager->flush();
        $token = $this->createMock(TokenInterface::class);
        $token
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
            $token
        );

        $authentificationSuccessListener->onSchebTwoFactorAuthenticationSuccess($event);

        $userAfterLogin = $userRepository->find($user->getId());

        $now = new \DateTimeImmutable();
        $diffSeconds = abs($now->getTimestamp() - $userAfterLogin->getLastLoginAt()->getTimestamp());
        $this->assertLessThan(5, $diffSeconds, 'lastLoginAt doit être très récent (moins de 5 secondes)');

        $this->assertNotNull($userAfterLogin->getLastLoginAt(), 'lastLoginAt doit être mis à jour après succès 2FA');
        $this->assertNotSame(hash('sha256', 'environnement précédent'), $userAfterLogin->getLastLoginFingerprint(), 'l\'empreinte de connexion doit être mise à jour');
        $this->assertEmailCount(1);
        $this->assertEmailSubjectContains($this->getMailerMessage(), 'Nouvelle connexion à votre compte');
        $historyEntries = $this->historyEntryRepository->findAll();
        $this->assertNotEmpty($historyEntries);
    }
}
