<?php

namespace App\Tests\Unit\Security\Voter;

use App\Entity\AutoAffectationRule;
use App\Entity\Territory;
use App\Entity\User;
use App\Entity\Zone;
use App\Security\Voter\ZoneVoter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ZoneVoterTest extends TestCase
{
    private const int ZONE_ID = 12;

    /**
     * @param ?array<string> $zoneToInclude
     * @param ?array<string> $zoneToExclude
     */
    #[DataProvider('provideRules')]
    public function testZoneDelete(string $status, ?array $zoneToInclude, ?array $zoneToExclude, int $expectedResult): void
    {
        $territory = new Territory();
        $territory->addAutoAffectationRule(
            (new AutoAffectationRule())->setStatus($status)->setZoneToInclude($zoneToInclude)->setZoneToExclude($zoneToExclude)
        );
        $zone = (new Zone())->setTerritory($territory);
        (new \ReflectionProperty(Zone::class, 'id'))->setValue($zone, self::ZONE_ID);

        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturn(true);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(new User());

        $vote = new Vote();
        $result = (new ZoneVoter($security))->vote($token, $zone, [ZoneVoter::ZONE_DELETE], $vote);

        $this->assertSame($expectedResult, $result);
        if (VoterInterface::ACCESS_DENIED === $expectedResult) {
            $this->assertStringContainsString('utilisée par une règle d\'auto-affectation active', implode(' ', $vote->reasons));
        }
    }

    public static function provideRules(): \Generator
    {
        yield 'no zone in rule' => [AutoAffectationRule::STATUS_ACTIVE, null, null, VoterInterface::ACCESS_GRANTED];
        yield 'other zones in active rule' => [AutoAffectationRule::STATUS_ACTIVE, ['1'], ['2'], VoterInterface::ACCESS_GRANTED];
        yield 'zone included in active rule' => [AutoAffectationRule::STATUS_ACTIVE, ['1', '12'], null, VoterInterface::ACCESS_DENIED];
        yield 'zone excluded in active rule' => [AutoAffectationRule::STATUS_ACTIVE, null, ['12'], VoterInterface::ACCESS_DENIED];
        yield 'zone included in archived rule' => [AutoAffectationRule::STATUS_ARCHIVED, ['12'], null, VoterInterface::ACCESS_GRANTED];
        yield 'zone excluded in archived rule' => [AutoAffectationRule::STATUS_ARCHIVED, null, ['12'], VoterInterface::ACCESS_GRANTED];
    }

    public function testZoneDeleteDeniedWithoutManageRights(): void
    {
        $zone = (new Zone())->setTerritory(new Territory());
        $security = $this->createStub(Security::class);
        $security->method('isGranted')->willReturn(false);
        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(new User());

        $this->assertSame(VoterInterface::ACCESS_DENIED, (new ZoneVoter($security))->vote($token, $zone, [ZoneVoter::ZONE_DELETE]));
    }
}
