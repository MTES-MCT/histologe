<?php

namespace App\Tests\Unit\Security\Voter;

use App\Entity\ServiceSecoursRoute;
use App\Security\User\ServiceSecoursUser;
use App\Security\Voter\ServiceSecoursRouteVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ServiceSecoursRouteVoterTest extends TestCase
{
    private ServiceSecoursRouteVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new ServiceSecoursRouteVoter(isSecurityEnabled: true);
    }

    public function testVoteWithInvalidAttribute(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $route = new ServiceSecoursRoute();

        $result = $this->voter->vote($token, $route, ['OTHER_ATTRIBUTE']);
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testVoteWithInvalidSubject(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $result = $this->voter->vote($token, new \stdClass(), [ServiceSecoursRouteVoter::ACCESS]);
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testVoteWhenUserNotAuthenticated(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $route = new ServiceSecoursRoute();

        $result = $this->voter->vote($token, $route, [ServiceSecoursRouteVoter::ACCESS]);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testVoteWhenUserIsDifferentType(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $user = $this->createMock(UserInterface::class);
        $token->method('getUser')->willReturn($user);

        $route = new ServiceSecoursRoute();

        $result = $this->voter->vote($token, $route, [ServiceSecoursRouteVoter::ACCESS]);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testVoteWhenRouteUuidDoesNotMatch(): void
    {
        $uuidA = \Symfony\Component\Uid\Uuid::v4();
        $uuidB = \Symfony\Component\Uid\Uuid::v4();
        $user = new ServiceSecoursUser('user@example.com:'.(string) $uuidA, 'user@example.com', (string) $uuidA);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $route = new ServiceSecoursRoute();
        $route->setUuid($uuidB);

        $result = $this->voter->vote($token, $route, [ServiceSecoursRouteVoter::ACCESS]);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testVoteWhenRouteUuidMatches(): void
    {
        $uuidA = \Symfony\Component\Uid\Uuid::v4();
        $user = new ServiceSecoursUser('user@example.com:'.(string) $uuidA, 'user@example.com', (string) $uuidA);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $route = new ServiceSecoursRoute();
        $route->setUuid($uuidA);

        $result = $this->voter->vote($token, $route, [ServiceSecoursRouteVoter::ACCESS]);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testVoteWhenSecurityIsDisabled(): void
    {
        $voterDisabled = new ServiceSecoursRouteVoter(isSecurityEnabled: false);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $route = new ServiceSecoursRoute();

        $result = $voterDisabled->vote($token, $route, [ServiceSecoursRouteVoter::ACCESS]);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }
}
