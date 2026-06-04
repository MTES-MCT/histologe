<?php

namespace App\Tests\Unit\Service\DashboardTabPanel\Kpi;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Entity\UserPartner;
use App\Service\DashboardTabPanel\Kpi\TabCountKpiCacheHelper;
use App\Service\DashboardTabPanel\TabQueryParameters;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class TabCountKpiCacheHelperTest extends TestCase
{
    private TabCountKpiCacheHelper $cacheHelper;

    protected function setUp(): void
    {
        $cache = $this->createMock(TagAwareCacheInterface::class);
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->cacheHelper = new TabCountKpiCacheHelper($cache, $parameterBag);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGenerateKeyForSuperAdmin(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        $user->setEmail('superadmin@test.fr');

        $params = new TabQueryParameters();

        $reflection = new \ReflectionClass(TabCountKpiCacheHelper::class);
        $method = $reflection->getMethod('generateKey');

        $key = $method->invoke($this->cacheHelper, TabCountKpiCacheHelper::NOUVEAUX_DOSSIERS, $user, $params);

        // Expected format: tab_kpi_nouveaux_dossiers-ROLE_ADMIN-territory_all-partner_all
        $this->assertStringContainsString('tab_kpi_nouveaux_dossiers', $key);
        $this->assertStringContainsString('ROLE_ADMIN', $key);
        $this->assertStringContainsString('territory_all', $key);
        $this->assertStringContainsString('partner_all', $key);
    }

    /**
     * @throws \ReflectionException
     */
    public function testGenerateKeyIncludesFilters(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER_PARTNER']);
        $user->setEmail('partner@test.fr');

        $territory = new Territory();
        $partner = new Partner();
        $partner->setTerritory($territory);
        $userPartner = new UserPartner()->setUser($user)->setPartner($partner);
        $user->addUserPartner($userPartner);

        $params = new TabQueryParameters(
            mesDossiersAverifier: '1',
            queryCommune: 'Marseille'
        );

        $reflection = new \ReflectionClass(TabCountKpiCacheHelper::class);
        $method = $reflection->getMethod('generateKey');

        $key = $method->invoke($this->cacheHelper, TabCountKpiCacheHelper::DOSSIERS_A_VERIFIER, $user, $params);

        $this->assertStringContainsString('commune_Marseille', $key);
        $this->assertStringContainsString('mesDossiersAverifier_', $key);
    }
}
