<?php

namespace App\Tests\Unit\Service\EmailAlert;

use App\Entity\User;
use App\Repository\Query\EmailAlert\PartnerQuery;
use App\Repository\Query\EmailAlert\UserQuery;
use App\Service\EmailAlert\EmailAlertChecker;
use PHPUnit\Framework\TestCase;

class EmailAlertCheckerTest extends TestCase
{
    private const string USER_01 = 'user-01+true@signal-logement.fr';
    private const string USER_02 = 'user-02+false@signal-logement.fr';
    private const string USER_03 = 'user-03+true@signal-logement.fr';

    public function testBuildUserEmailAlertReturnsTrueOnlyForEmailsWithIssue(): void
    {
        $userQuery = $this->createMock(UserQuery::class);
        $partnerQuery = $this->createMock(PartnerQuery::class);
        $emailAlertChecker = new EmailAlertChecker($partnerQuery, $userQuery);

        $user1 = $this->createConfiguredMock(User::class, ['getEmail' => self::USER_01]);
        $user2 = $this->createConfiguredMock(User::class, ['getEmail' => self::USER_02]);
        $user3 = $this->createConfiguredMock(User::class, ['getEmail' => self::USER_03]);

        $users = [$user1, $user2, $user3];

        $userQuery
            ->expects($this->once())
            ->method('findEmailsWithIssue')
            ->with([self::USER_01, self::USER_02, self::USER_03])
            ->willReturn(['user-01+true@signal-logement.fr', 'user-03+true@signal-logement.fr']);

        $result = $emailAlertChecker->buildUserEmailAlert($users);

        $this->assertSame(
            [
                self::USER_01 => true,
                self::USER_03 => true,
            ],
            $result
        );
    }
}
