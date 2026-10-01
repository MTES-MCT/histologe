<?php

namespace App\Tests\Functional\Repository\Query\User;

use App\Repository\Query\User\UserAffectationQuery;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserAffectationQueryTest extends KernelTestCase
{
    public function testFindInactiveUserWithNbAffectations(): void
    {
        /** @var UserAffectationQuery $userAffectationQuery */
        $userAffectationQuery = static::getContainer()->get(UserAffectationQuery::class);

        $users = $userAffectationQuery->findInactiveWithNbAffectationPending();

        $this->assertIsArray($users);
        $this->assertCount(11, $users);
        foreach ($users as $user) {
            $this->assertArrayHasKey('email', $user);
            if (!empty($user['signalements'])) {
                $this->assertEquals($user['nb_signalements'], \count(explode(',', (string) $user['signalements'])));
            }
        }
    }
}
