<?php

namespace App\Tests\Unit\Service\Gouv\ProConnect;

use App\Service\Gouv\ProConnect\ProConnectContext;
use PHPUnit\Framework\TestCase;
use Random\RandomException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class ProConnectContextTest extends TestCase
{
    private ProConnectContext $context;
    private Session $session;

    protected function setUp(): void
    {
        $requestStack = new RequestStack();
        $this->session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($this->session);
        $requestStack->push($request);

        $this->context = new ProConnectContext($requestStack, 'wiremock.local');
    }

    /**
     * @throws RandomException
     */
    public function testGenerateStateStoresAndReturnsFakeState(): void
    {
        $state = $this->context->generateState();

        $this->assertSame('fake_state', $state);
        $this->assertSame('fake_state', $this->session->get(ProConnectContext::SESSION_KEY_STATE));
    }

    /**
     * @throws RandomException
     */
    public function testGenerateNonceStoresAndReturnsFakeNonce(): void
    {
        $nonce = $this->context->generateNonce();

        $this->assertSame('fake_nonce', $nonce);
        $this->assertSame('fake_nonce', $this->session->get(ProConnectContext::SESSION_KEY_NONCE));
    }

    /**
     * @throws RandomException
     */
    public function testIsValidStateReturnsTrueWhenMatching(): void
    {
        $this->context->generateState();
        $this->assertTrue($this->context->isValidState('fake_state'));
    }

    public function testIsValidStateReturnsFalseWhenNotMatching(): void
    {
        $this->context->generateState();
        $this->assertFalse($this->context->isValidState('invalid_state'));
    }

    /**
     * @throws RandomException
     */
    public function testClearRemovesStateAndNonce(): void
    {
        $this->context->generateState();
        $this->context->generateNonce();
        $this->context->clear();

        $this->assertNull($this->session->get(ProConnectContext::SESSION_KEY_STATE));
        $this->assertNull($this->session->get(ProConnectContext::SESSION_KEY_NONCE));
    }
}
