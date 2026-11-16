<?php

namespace App\Tests\Unit\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\LogoutSubscriber;
use App\Security\User\SignalementUser;
use App\Service\Gouv\ProConnect\ProConnectAuthentication;
use App\Service\Gouv\ProConnect\ProConnectContext;
use App\Tests\UserHelper;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutSubscriberTest extends TestCase
{
    use UserHelper;

    private ProConnectAuthentication&Stub $proConnectAuth;
    private ProConnectContext&Stub $proConnectContext;
    private UrlGeneratorInterface&Stub $urlGenerator;
    private LoggerInterface&Stub $logger;
    private TokenInterface&Stub $token;
    private ?User $user = null;

    protected function setUp(): void
    {
        $this->proConnectAuth = $this->createStub(ProConnectAuthentication::class);
        $this->proConnectContext = $this->createStub(ProConnectContext::class);
        $this->urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);

        $this->user = $this->getUserPronnected();
        $this->token = $this->createStub(TokenInterface::class);
        $this->token->method('getUser')->willReturn($this->user);
    }

    public function testLogoutRedirectsToProConnectAndClearsSession(): void
    {
        $request = new Request();
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('has')
            ->with('proconnect_id_token')
            ->willReturn(true);
        $request->setSession($session);

        $event = new LogoutEvent($request, $this->token);

        $this->proConnectAuth->method('getLogoutUrl')->willReturn('https://proconnect/logout');

        $subscriber = new LogoutSubscriber(
            $this->proConnectAuth,
            $this->proConnectContext,
            $this->urlGenerator,
            $this->logger,
        );

        $subscriber->onLogout($event);
        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://proconnect/logout', $response->getTargetUrl());
    }

    public function testLogoutHandlesExceptionAndAddsFlash(): void
    {
        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag
            ->expects($this->once())
            ->method('add')
            ->with(
                'warning',
                $this->stringContains('n\'a pas pu être effectuée automatiquement')
            );

        $request = new Request();
        /** @var Stub&FlashBagAwareSessionInterface $session */
        $session = $this->createStub(FlashBagAwareSessionInterface::class);
        $session->method('has')->willReturn(true);
        $session->method('getFlashBag')->willReturn($flashBag);

        $request->setSession($session);
        $event = new LogoutEvent($request, $this->token);

        $this->proConnectAuth->method('getLogoutUrl')->willThrowException(new \RuntimeException('Network down'));
        $this->urlGenerator->method('generate')->willReturn('/login');
        $subscriber = new LogoutSubscriber(
            $this->proConnectAuth,
            $this->proConnectContext,
            $this->urlGenerator,
            $this->logger,
        );

        $subscriber->onLogout($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/login', $response->getTargetUrl());
    }

    public function testLogoutRedirectsForSignalementUser(): void
    {
        /** @var Stub&SignalementUser $user */
        $user = $this->createStub(SignalementUser::class);
        $user->method('getCodeSuivi')->willReturn('123456789');
        $user->method('getUserIdentifier')->willReturn('123456789:occupant');

        /** @var Stub&TokenInterface $token */
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects($this->once())
            ->method('invalidate');

        $request = new Request();
        $request->setSession($session);

        $event = new LogoutEvent($request, $token);

        $proConnectContext = $this->createMock(ProConnectContext::class);
        $proConnectContext
            ->expects($this->once())
            ->method('clearSession');

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($this->equalTo('home'), $this->anything())
            ->willReturn('/');

        $subscriber = new LogoutSubscriber(
            $this->proConnectAuth,
            $proConnectContext,
            $urlGenerator,
            $this->logger,
        );

        $subscriber->onLogout($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/', $response->getTargetUrl());
    }
}
