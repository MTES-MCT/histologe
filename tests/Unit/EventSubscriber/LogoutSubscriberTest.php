<?php

namespace App\Tests\Unit\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\LogoutSubscriber;
use App\Security\User\SignalementUser;
use App\Service\Gouv\ProConnect\ProConnectAuthentication;
use App\Service\Gouv\ProConnect\ProConnectContext;
use App\Tests\UserHelper;
use PHPUnit\Framework\MockObject\MockObject;
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

    private ProConnectAuthentication|MockObject $proConnectAuth;
    private ProConnectContext|MockObject $proConnectContext;
    private UrlGeneratorInterface|MockObject $urlGenerator;
    private LoggerInterface|MockObject $logger;
    private SessionInterface|MockObject $session;
    private TokenInterface|MockObject $token;
    private ?User $user = null;

    protected function setUp(): void
    {
        $this->session = $this->createMock(SessionInterface::class);
        $this->proConnectAuth = $this->createMock(ProConnectAuthentication::class);
        $this->proConnectContext = $this->createMock(ProConnectContext::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->user = $this->getUserPronnected();
        $this->token = $this->createMock(TokenInterface::class);
        $this->token->method('getUser')->willReturn($this->user);
    }

    public function testLogoutRedirectsToProConnectAndClearsSession(): void
    {
        $request = new Request();
        $this->session
            ->expects($this->once())
            ->method('has')
            ->with('proconnect_id_token')
            ->willReturn(true);
        $request->setSession($this->session);

        $event = new LogoutEvent($request, $this->token);

        $this->proConnectAuth = $this->createMock(ProConnectAuthentication::class);
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
        $session = $this->createMock(FlashBagAwareSessionInterface::class);
        $session->method('has')->willReturn(true);
        $session->method('getFlashBag')->willReturn($flashBag);

        $request->setSession($session);
        $event = new LogoutEvent($request, $this->token);

        $this->proConnectAuth->method('getLogoutUrl')->willThrowException(new \RuntimeException('Network down'));
        $this->urlGenerator->method('generate')->willReturn('/login');
        $logger = $this->createMock(LoggerInterface::class);

        $subscriber = new LogoutSubscriber(
            $this->proConnectAuth,
            $this->proConnectContext,
            $this->urlGenerator,
            $logger,
        );

        $subscriber->onLogout($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/login', $response->getTargetUrl());
    }

    public function testLogoutRedirectsForSignalementUser(): void
    {
        $user = $this->createMock(SignalementUser::class);
        $user->method('getCodeSuivi')->willReturn('123456789');
        $user->method('getUserIdentifier')->willReturn('123456789:occupant');

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $this->session
            ->expects($this->once())
            ->method('invalidate');

        $request = new Request();
        $request->setSession($this->session);

        $event = new LogoutEvent($request, $token);

        $this->proConnectContext
            ->expects($this->once())
            ->method('clearSession');

        $this->urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($this->equalTo('home'), $this->anything())
            ->willReturn('/');

        $subscriber = new LogoutSubscriber(
            $this->proConnectAuth,
            $this->proConnectContext,
            $this->urlGenerator,
            $this->logger,
        );

        $subscriber->onLogout($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/', $response->getTargetUrl());
    }
}
