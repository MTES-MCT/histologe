<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\DashboardTabPanel\TabBodyLoader;

use App\Entity\User;
use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyLoader\DossierDemandesFermetureUsagerTabBodyLoader;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManagerInterface;
use App\Service\DashboardTabPanel\TabDossier;
use App\Service\DashboardTabPanel\TabDossierResult;
use App\Service\DashboardTabPanel\TabQueryParameters;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class DossierDemandesFermetureUsagerTabBodyLoaderTest extends TestCase
{
    public function testLoadFillsTabBodyWithExpectedData(): void
    {
        /** @var User&MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('isSuperAdmin')->willReturn(true);

        /** @var Security&MockObject $security */
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->willReturn(true);
        $security->method('getUser')->willReturn($user);

        /** @var TabDataManagerInterface&MockObject $TabDataManagerInterface */
        $TabDataManagerInterface = $this->createMock(TabDataManagerInterface::class);
        $tabQueryParameters = new TabQueryParameters(territoireId: 42);

        $expectedDossiers = [new TabDossier(), new TabDossier()];
        $expectedCount = 2;
        $tabDossierResult = new TabDossierResult($expectedDossiers, $expectedCount);

        $TabDataManagerInterface->expects($this->once())
            ->method('getDossiersDemandesFermetureByUsager')
            ->with($tabQueryParameters)
            ->willReturn($tabDossierResult);

        $loader = new DossierDemandesFermetureUsagerTabBodyLoader($security, $TabDataManagerInterface);
        $tabBody = new TabBody(
            type: TabBodyType::TAB_DATA_TYPE_DOSSIERS_RELANCE_USAGER_SANS_REPONSE,
            tabQueryParameters: $tabQueryParameters
        );

        $loader->load($tabBody);

        $this->assertSame($expectedDossiers, $tabBody->getData());
        $this->assertSame($expectedCount, $tabBody->getCount());

        $filters = $tabBody->getFilters();
        $this->assertArrayHasKey('usagerAbandonProcedure', $filters);
        $this->assertSame(1, $filters['usagerAbandonProcedure']);

        $this->assertSame(
            'back/dashboard/tabs/dossiers_a_fermer/_body_dossier_demande_fermeture_usager.html.twig',
            $tabBody->getTemplate()
        );

        $this->assertSame('ASC', $tabQueryParameters->orderBy);
    }

    public function testLoadKeepsExistingOrderBy(): void
    {
        /** @var Security&MockObject $security */
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->willReturn(true);

        /** @var TabDataManagerInterface&MockObject $TabDataManagerInterface */
        $TabDataManagerInterface = $this->createMock(TabDataManagerInterface::class);
        $tabQueryParameters = new TabQueryParameters(territoireId: 99, orderBy: 'DESC');

        $expectedDossiers = [new TabDossier()];
        $expectedCount = 1;
        $tabDossierResult = new TabDossierResult($expectedDossiers, $expectedCount);

        $TabDataManagerInterface->expects($this->once())
            ->method('getDossiersDemandesFermetureByUsager')
            ->with($tabQueryParameters)
            ->willReturn($tabDossierResult);

        $loader = new DossierDemandesFermetureUsagerTabBodyLoader($security, $TabDataManagerInterface);
        $tabBody = new TabBody(
            type: TabBodyType::TAB_DATA_TYPE_DOSSIERS_RELANCE_USAGER_SANS_REPONSE,
            tabQueryParameters: $tabQueryParameters
        );

        $loader->load($tabBody);

        $this->assertSame('DESC', $tabQueryParameters->orderBy, 'OrderBy ne doit pas être écrasé si déjà défini.');
    }
}
