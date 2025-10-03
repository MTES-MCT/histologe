<?php

namespace App\Tests\Functional\Repository\Query\SignalementList;

use App\Repository\Query\SignalementList\MapGeoDataQuery;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MapGeoDataQueryTest extends KernelTestCase
{
    private const string USER_ADMIN = 'admin-01@signal-logement.fr';
    private const string USER_ADMIN_MULTI_13 = 'admin-partenaire-multi-ter-13-01@signal-logement.fr';
    private const string USER_AGENT_MULTI_34 = 'user-partenaire-multi-ter-34-30@signal-logement.fr';

    /**
     * @dataProvider provideSearchWithGeoData
     * @param array<string, mixed> $options
     */
    public function testGetData(string $email, array $options, int $nbResult): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => $email]);
        /** @var MapGeoDataQuery $mapGeoDataQuery */
        $mapGeoDataQuery = static::getContainer()->get(MapGeoDataQuery::class);
        $signalements = $mapGeoDataQuery->getData($user, $options, 0);
        $this->assertCount($nbResult, $signalements);
    }

    public function provideSearchWithGeoData(): \Generator
    {
        yield 'Search all for super admin' => [self::USER_ADMIN, [], 47];
        yield 'Search in Marseille for super admin' => [self::USER_ADMIN, ['cities' => ['Marseille']], 25];
        yield 'Search all for admin partner multi territories' => [self::USER_ADMIN_MULTI_13, [], 6];
        yield 'Search in Ain for admin partner multi territories' => [self::USER_ADMIN_MULTI_13, ['territories' => 1], 1];
        yield 'Search all for user partner multi territories' => [self::USER_AGENT_MULTI_34, [], 2];
        yield 'Search in Hérault for user partner multi territories' => [self::USER_AGENT_MULTI_34, ['territories' => 35], 1];
    }
}
