<?php

namespace App\Tests\Functional\Repository\Query\SignalementList;

use App\Entity\Enum\SignalementStatus;
use App\Entity\User;
use App\Repository\Query\SignalementList\QueryBuilderFactory;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class QueryBuilderFactoryTest extends KernelTestCase
{
    /**
     * @param array<string, mixed> $userConfig
     * @param array<string, mixed> $options
     * @param array<int, string>   $expectedDqlParts
     * @param array<string, mixed> $expectedParams
     *
     * @dataProvider userOptionsProvider
     *
     * @throws Exception
     */
    public function testFindSignalementListQueryBuilder(
        array $userConfig,
        array $options,
        array $expectedDqlParts,
        array $expectedParams,
    ): void {
        /** @var User&MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('isUserPartner')->willReturn($userConfig['isUserPartner']);
        $user->method('isPartnerAdmin')->willReturn($userConfig['isPartnerAdmin']);
        $user->method('isTerritoryAdmin')->willReturn($userConfig['isTerritoryAdmin']);
        $user->method('getPartners')->willReturn(new ArrayCollection($userConfig['partners'] ?? []));
        $user->method('getPartnersTerritories')->willReturn($userConfig['territories'] ?? []);

        /** @var QueryBuilderFactory $queryBuilderFactory */
        $queryBuilderFactory = static::getContainer()->get(QueryBuilderFactory::class);
        $qb = $queryBuilderFactory->create($user, $options);

        $dql = $qb->getDQL();
        $params = $qb->getParameters();

        // Vérifie que les morceaux de DQL attendus sont présents
        foreach ($expectedDqlParts as $part) {
            $this->assertStringContainsString($part, $dql);
        }

        // Vérifie que les paramètres sont bien définis
        $getParamValue = fn (string $name) => array_reduce(
            $params->toArray(),
            fn ($carry, $param) => $param->getName() === $name ? $param->getValue() : $carry,
            null
        );

        foreach ($expectedParams as $paramName => $expectedValue) {
            $value = $getParamValue($paramName);
            $this->assertNotNull($value, "Param $paramName should exist");
            $this->assertEquals($expectedValue, $value);
        }
    }

    /**
     * @return array<string, array<mixed, mixed>>
     */
    public function userOptionsProvider(): array
    {
        return [
            'Partner user, simple options' => [
                ['isUserPartner' => true, 'isPartnerAdmin' => false, 'isTerritoryAdmin' => false, 'partners' => [], 'territories' => []],
                ['statuses' => [SignalementStatus::ACTIVE->value], 'sortBy' => 'reference', 'orderBy' => 'ASC'],
                ['LEFT JOIN s.affectations', 'LEFT JOIN a.partner', 's.id IN'],
                ['statusList' => [SignalementStatus::ARCHIVED, SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED]],
            ],
            'Partner admin with bailleur' => [
                ['isUserPartner' => false, 'isPartnerAdmin' => true, 'isTerritoryAdmin' => false, 'partners' => [], 'territories' => []],
                ['bailleurSocial' => 'LOGEMENT1', 'statuses' => [SignalementStatus::ACTIVE->value]],
                ['AND s.bailleur = :bailleur', 'LEFT JOIN s.affectations', 'DISTINCT IDENTITY(a2.signalement)'],
                [
                    'statusList' => [SignalementStatus::ARCHIVED, SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED],
                    'bailleur' => 'LOGEMENT1',
                    'partners' => new ArrayCollection([]),
                    'statut_affectation' => [SignalementStatus::ACTIVE->mapAffectationStatus()],
                ],
            ],
            'Territory admin with empty territories' => [
                ['isUserPartner' => false, 'isPartnerAdmin' => false, 'isTerritoryAdmin' => true, 'partners' => [], 'territories' => [1, 2]],
                [],
                ['s.territory IN (:territories)'],
                [
                    'statusList' => [SignalementStatus::ARCHIVED, SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED],
                    'territories' => [1, 2],
                ],
            ],
        ];
    }
}
