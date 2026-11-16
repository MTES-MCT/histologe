<?php

namespace App\Tests\Unit\Security\Voter;

use App\Security\Voter\ServiceSecoursVoter;
use App\Security\Voter\TabPanelVoter;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TabPanelVoterTest extends TestCase
{
    use FixturesHelper;

    public function testVoteDeniesAccessWhenUserLacksPermissionAffectation(): void
    {
        /** @var Stub&TokenInterface $token */
        $token = $this->createStub(TokenInterface::class);

        $user = $this->getUser(['ROLE_USER']);
        $user->setHasPermissionAffectation(false);
        $token->method('getUser')->willReturn($user);

        $voter = new TabPanelVoter($this->createStub(Security::class));
        $subject = TabBodyType::TAB_DATA_TYPE_DOSSIERS_NON_AFFECTATION;

        $voteResult = $voter->vote($token, $subject, [TabPanelVoter::TAB_PANEL_VIEW]);

        $this->assertSame(-1, $voteResult, 'Voter should deny access when the user lacks USER_PERMISSION_AFFECTATION.');
    }

    public function testVoteDeniesAccessWhenUserIsNotAuthenticated(): void
    {
        /** @var Stub&TokenInterface $token */
        $token = $this->createStub(TokenInterface::class);

        $token->method('getUser')->willReturn(null);

        $voter = new TabPanelVoter($this->createStub(Security::class));
        $subject = TabBodyType::TAB_DATA_TYPE_DOSSIERS_NON_AFFECTATION;

        $voteResult = $voter->vote($token, $subject, [TabPanelVoter::TAB_PANEL_VIEW]);

        $this->assertSame(-1, $voteResult, 'Voter should deny access for unauthenticated users.');
    }

    public function testVoteDeniesAccessForUnknownTabType(): void
    {
        /** @var Stub&TokenInterface $token */
        $token = $this->createStub(TokenInterface::class);

        $user = $this->getUser(['ROLE_ADMIN']);
        $user->setHasPermissionAffectation(false);
        $token->method('getUser')->willReturn($user);

        $voter = new TabPanelVoter($this->createStub(Security::class));
        $subject = 'unknown-tab-type';

        $voteResult = $voter->vote($token, $subject, [TabPanelVoter::TAB_PANEL_VIEW]);

        $this->assertSame(-1, $voteResult, 'Voter should deny access for an unknown tab type.');
    }

    public function testVoteGrantsServiceSecoursTabWhenTerritoryHasRoutes(): void
    {
        /** @var Stub&TokenInterface $token */
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($this->getUser(['ROLE_ADMIN_TERRITORY']));

        /** @var MockObject&Security $security */
        $security = $this->createMock(Security::class);
        $security->expects($this->once())->method('isGranted')->with(ServiceSecoursVoter::SERVICE_SECOURS_SEE)->willReturn(true);

        $voter = new TabPanelVoter($security);
        $subject = TabBodyType::TAB_DATA_TYPE_DOSSIERS_FORM_SERVICE_SECOURS;

        $voteResult = $voter->vote($token, $subject, [TabPanelVoter::TAB_PANEL_VIEW]);

        $this->assertSame(1, $voteResult, 'Voter should grant access when the territory has service secours routes.');
    }

    public function testVoteDeniesServiceSecoursTabWhenTerritoryHasNoRoute(): void
    {
        /** @var Stub&TokenInterface $token */
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($this->getUser(['ROLE_ADMIN_TERRITORY']));

        /** @var MockObject&Security $security */
        $security = $this->createMock(Security::class);
        $security->expects($this->once())->method('isGranted')->with(ServiceSecoursVoter::SERVICE_SECOURS_SEE)->willReturn(false);

        $voter = new TabPanelVoter($security);
        $subject = TabBodyType::TAB_DATA_TYPE_DOSSIERS_FORM_SERVICE_SECOURS;

        $voteResult = $voter->vote($token, $subject, [TabPanelVoter::TAB_PANEL_VIEW]);

        $this->assertSame(-1, $voteResult, 'Voter should deny access when the territory has no service secours route.');
    }
}
