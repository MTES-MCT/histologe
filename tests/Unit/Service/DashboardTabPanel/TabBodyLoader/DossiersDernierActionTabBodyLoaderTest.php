<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\DashboardTabPanel\TabBodyLoader;

use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyLoader\DossiersDernierActionTabBodyLoader;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManager;
use App\Service\DashboardTabPanel\TabQueryParameters;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class DossiersDernierActionTabBodyLoaderTest extends TestCase
{
    public function testLoadFillsTabBodyWithExpectedData(): void
    {
        /** @var Security&MockObject $security */
        $security = $this->createMock(Security::class);
        $security->method('isGranted')->willReturn(true);

        /** @var TabDataManager&MockObject $tabDataManager */
        $tabDataManager = $this->createMock(TabDataManager::class);
        $tabQueryParameters = new TabQueryParameters(territoireId: 42);

        $expectedData = ['foo' => 'bar'];
        $expectedKpi = [
            'comptes_en_attente' => 3,
            'partenaires_non_notifiables' => 2,
            'partenaires_interfaces' => 1,
        ];
        $expectedInterconnexion = ['hasErrorsLastDay' => false];

        $tabDataManager->expects($this->once())
            ->method('getDernierActionDossiers')
            ->with($tabQueryParameters)
            ->willReturn($expectedData);
        $tabDataManager->expects($this->once())
            ->method('countUsersPendingToArchive')
            ->with($tabQueryParameters)
            ->willReturn($expectedKpi['comptes_en_attente']);
        $tabDataManager->expects($this->once())
            ->method('countPartenairesNonNotifiables')
            ->with($tabQueryParameters)
            ->willReturn($expectedKpi['partenaires_non_notifiables']);
        $tabDataManager->expects($this->once())
            ->method('countPartenairesInterfaces')
            ->with($tabQueryParameters)
            ->willReturn($expectedKpi['partenaires_interfaces']);
        $tabDataManager->expects($this->once())
            ->method('getInterconnexions')
            ->with($tabQueryParameters)
            ->willReturn($expectedInterconnexion);

        $loader = new DossiersDernierActionTabBodyLoader($security, $tabDataManager);
        $tabBody = new TabBody(
            type: TabBodyType::TAB_DATA_TYPE_DERNIER_ACTION_DOSSIERS,
            tabQueryParameters: $tabQueryParameters
        );

        $loader->load($tabBody);

        $data = $tabBody->getData();
        $this->assertIsArray($data);
        $this->assertSame($expectedData, $data['data']);
        $this->assertSame($expectedKpi, $data['data_kpi']);
        $this->assertSame($expectedInterconnexion, $data['data_interconnexion']);
        $this->assertSame(42, $data['territory_id']);
        $this->assertSame('back/dashboard/tabs/accueil/_body_derniere_action_dossiers.html.twig', $tabBody->getTemplate());
    }
}
