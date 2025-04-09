<?php

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\LogoutSubscriber;
use App\Service\Gouv\ProConnect\ProConnectAuthentication;
use App\Service\Gouv\ProConnect\ProConnectContext;
use App\Tests\UserHelper;
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

    public function testLogoutRedirectsToProConnectAndClearsSession(): void
    {
        $user = $this->getUserPronnected();

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $request = new Request();
        $session = $this->createMock(SessionInterface::class);
        $request->setSession($session);

        $event = new LogoutEvent($request, $token);

        $proConnectAuth = $this->createMock(ProConnectAuthentication::class);
        $proConnectAuth->method('getLogoutUrl')->willReturn('https://proconnect/logout');

        $context = $this->createMock(ProConnectContext::class);
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $logger = $this->createMock(LoggerInterface::class);

        $subscriber = new LogoutSubscriber(
            $proConnectAuth,
            $context,
            $urlGenerator,
            $logger,
            featureProConnect: 1
        );

        $subscriber->onLogout($event);
        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://proconnect/logout', $response->getTargetUrl());
    }

    public function testLogoutHandlesExceptionAndAddsFlash(): void
    {
        $user = $this->getUserPronnected();

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

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
        $session
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $request->setSession($session);

        $event = new LogoutEvent($request, $token);

        $proConnectAuth = $this->createMock(ProConnectAuthentication::class);
        $proConnectAuth->method('getLogoutUrl')->willThrowException(new \RuntimeException('Network down'));

        $context = $this->createMock(ProConnectContext::class);
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/login');

        $logger = $this->createMock(LoggerInterface::class);

        $subscriber = new LogoutSubscriber(
            $proConnectAuth,
            $context,
            $urlGenerator,
            $logger,
            featureProConnect: 1
        );

        // Act
        $subscriber->onLogout($event);

        // Assert
        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/login', $response->getTargetUrl());
    }
}
