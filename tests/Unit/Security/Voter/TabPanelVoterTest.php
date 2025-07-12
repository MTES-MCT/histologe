<?php

namespace App\Tests\Unit\Security\Voter;

use App\Security\Voter\TabPanelVoter;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TabPanelVoterTest extends TestCase
{
    use FixturesHelper;

    public function testVoteGrantsAccessWhenUserHasPermissionAffectation(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $user = $this->getUser(['ROLE_USER']);
        $user->setHasPermissionAffectation(true);
        $token->method('getUser')->willReturn($user);

        $voter = new TabPanelVoter();
        $subject = TabBodyType::TAB_DATA_TYPE_DOSSIERS_NON_AFFECTATION;

        $voteResult = $voter->vote($token, $subject, [TabPanelVoter::VIEW_TAB_PANEL]);

        $this->assertSame(1, $voteResult, 'Voter should grant access when the user has USER_PERMISSION_AFFECTATION.');
    }

    public function testVoteDeniesAccessWhenUserLacksPermissionAffectation(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $user = $this->getUser(['ROLE_USER']);
        $user->setHasPermissionAffectation(false);
        $token->method('getUser')->willReturn($user);

        $voter = new TabPanelVoter();
        $subject = TabBodyType::TAB_DATA_TYPE_DOSSIERS_NON_AFFECTATION;

        $voteResult = $voter->vote($token, $subject, [TabPanelVoter::VIEW_TAB_PANEL]);

        $this->assertSame(-1, $voteResult, 'Voter should deny access when the user lacks USER_PERMISSION_AFFECTATION.');
    }

    public function testVoteDeniesAccessWhenUserIsNotAuthenticated(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $token->method('getUser')->willReturn(null);

        $voter = new TabPanelVoter();
        $subject = TabBodyType::TAB_DATA_TYPE_DOSSIERS_NON_AFFECTATION;

        $voteResult = $voter->vote($token, $subject, [TabPanelVoter::VIEW_TAB_PANEL]);

        $this->assertSame(-1, $voteResult, 'Voter should deny access for unauthenticated users.');
    }

    public function testVoteDeniesAccessForUnknownTabType(): void
    {
        $token = $this->createMock(TokenInterface::class);

        $user = $this->getUser(['ROLE_ADMIN']);
        $user->setHasPermissionAffectation(false);
        $token->method('getUser')->willReturn($user);

        $voter = new TabPanelVoter();
        $subject = 'unknown-tab-type';

        $voteResult = $voter->vote($token, $subject, [TabPanelVoter::VIEW_TAB_PANEL]);

        $this->assertSame(-1, $voteResult, 'Voter should deny access for an unknown tab type.');
    }
}
