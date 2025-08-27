<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\DashboardTabPanel\TabBodyLoader;

use App\Entity\User;
use App\Service\DashboardTabPanel\TabBody;
use App\Service\DashboardTabPanel\TabBodyLoader\DossiersMessagesUsagersSansReponseTabBodyLoader;
use App\Service\DashboardTabPanel\TabBodyType;
use App\Service\DashboardTabPanel\TabDataManager;
use App\Service\DashboardTabPanel\TabDossier;
use App\Service\DashboardTabPanel\TabDossierResult;
use App\Service\DashboardTabPanel\TabQueryParameters;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class DossiersMessagesUsagersSansReponseTabBodyLoaderTest extends TestCase
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

        /** @var TabDataManager&MockObject $tabDataManager */
        $tabDataManager = $this->createMock(TabDataManager::class);

        $tabQueryParameters = new TabQueryParameters(mesDossiersMessagesUsagers: '1');
        $expectedResult = new TabDossierResult(
            dossiers: [new TabDossier(), new TabDossier()],
            count: 2,
        );

        $tabDataManager->expects($this->once())
            ->method('getMessagesUsagersMessagesSansReponse')
            ->with($tabQueryParameters)
            ->willReturn($expectedResult);

        $loader = new DossiersMessagesUsagersSansReponseTabBodyLoader($security, $tabDataManager);
        $tabBody = new TabBody(
            type: TabBodyType::TAB_DATA_TYPE_DOSSIERS_MESSAGES_USAGERS_SANS_REPONSE,
            tabQueryParameters: $tabQueryParameters
        );

        $loader->load($tabBody);

        $this->assertSame($expectedResult->dossiers, $tabBody->getData());
        $this->assertSame($expectedResult->count, $tabBody->getCount());
        $filters = $tabBody->getFilters();
        $this->assertSame('oui', $filters['isMessageWithoutResponse']);
        $this->assertSame('oui', $filters['showMySignalementsOnly']);
        $this->assertSame('back/dashboard/tabs/dossiers_messages_usagers/_body_dossier_messages_usagers_sans_reponse.html.twig', $tabBody->getTemplate());
    }
}
