<?php

namespace App\Tests\Unit;

use App\Entity\User;
use App\Service\CacheCommonKeyGenerator;
use App\Tests\FixturesHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class CacheCommonKeyGeneratorTest extends TestCase
{
    use FixturesHelper;

    public function testGenerate(): void
    {
        $securityMock = $this->createMock(Security::class);
        $securityMock
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->getUser([User::ROLE_USER_PARTNER]));

        $cacheCommonKeyGenerator = new CacheCommonKeyGenerator($securityMock);
        $key = $cacheCommonKeyGenerator->generate();

        $this->assertEquals('ROLE_USER_PARTNER-partnerId-1', $key);
    }
}
