<?php

namespace App\Tests\Unit\Service\Menu;

use App\Entity\User;
use App\Service\Menu\MenuBuilder;
use App\Tests\FixturesHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class MenuBuilderTest extends KernelTestCase
{
    use FixturesHelper;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testBuild()
    {
        $user = $this->getUser([User::ROLE_ADMIN]);
        $requestStack = new RequestStack();
        $currentRequest = new Request();
        $currentRequest->attributes->set('_route', 'random_route');
        $currentRequest->attributes->set('_user', $user);
        $requestStack->push($currentRequest);

        $parameterBag = self::getContainer()->get(ParameterBagInterface::class);
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);
        $menuBuilder = new MenuBuilder($requestStack, $parameterBag, $security);
        $this->assertCount(5, $menuBuilder->build()->getChildren());
        $this->assertEquals('Tableau de bord', $menuBuilder->build()->getChildren()[0]->getLabel());
        $this->assertEquals('Signalements', $menuBuilder->build()->getChildren()[1]->getLabel());
        $this->assertEquals('Données chiffrées', $menuBuilder->build()->getChildren()[2]->getLabel());
        $this->assertEquals('Outils Admin', $menuBuilder->build()->getChildren()[3]->getLabel());
        $this->assertEquals('Outils SA', $menuBuilder->build()->getChildren()[4]->getLabel());
    }
}
