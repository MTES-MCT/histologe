<?php

namespace App\Tests\Unit\Service\ServiceSecours;

use App\Entity\Enum\ServiceSecoursCategory;
use App\Entity\ServiceSecoursRoute;
use App\Entity\Territory;
use App\Exception\ServiceSecours\ServiceSecoursRouteResolutionException;
use App\Repository\ServiceSecoursRouteRepository;
use App\Service\Gouv\Insee\Response\SiretInformation;
use App\Service\Gouv\Insee\SireneService;
use App\Service\Gouv\ProConnect\Model\ProConnectUser;
use App\Service\ServiceSecours\ServiceSecoursCategoryResolver;
use App\Service\ServiceSecours\ServiceSecoursRouteResolver;
use App\Service\Signalement\ZipcodeProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ServiceSecoursRouteResolverTest extends TestCase
{
    private SireneService&MockObject $sireneService;
    private ServiceSecoursCategoryResolver&MockObject $serviceSecoursCategoryResolver;
    private ZipcodeProvider&MockObject $zipcodeProvider;
    private ServiceSecoursRouteRepository&MockObject $serviceSecoursRouteRepository;
    private ServiceSecoursRouteResolver $resolver;

    protected function setUp(): void
    {
        $this->sireneService = $this->createMock(SireneService::class);
        $this->serviceSecoursCategoryResolver = $this->createMock(ServiceSecoursCategoryResolver::class);
        $this->zipcodeProvider = $this->createMock(ZipcodeProvider::class);
        $this->serviceSecoursRouteRepository = $this->createMock(ServiceSecoursRouteRepository::class);

        $this->resolver = new ServiceSecoursRouteResolver(
            $this->sireneService,
            $this->serviceSecoursCategoryResolver,
            $this->zipcodeProvider,
            $this->serviceSecoursRouteRepository,
        );
    }

    public function testResolveSuccessfulSdisRoute(): void
    {
        $proConnectUser = new ProConnectUser([
            'uid' => '123',
            'sub' => 'sub_123',
            'email' => 'agent@sdis13.fr',
            'siret' => '12345678901234',
            'organizational_unit' => 'SDIS 13',
        ]);

        $territory = new Territory();
        $expectedRoute = new ServiceSecoursRoute();

        $this->sireneService
            ->expects($this->once())
            ->method('getSiret')
            ->with('12345678901234')
            ->willReturn(new SiretInformation('12345678901234', '13001'));

        $this->zipcodeProvider
            ->expects($this->once())
            ->method('getTerritoryByInseeCode')
            ->with('13001')
            ->willReturn($territory);

        $this->serviceSecoursCategoryResolver
            ->expects($this->once())
            ->method('resolve')
            ->with('SDIS 13')
            ->willReturn(ServiceSecoursCategory::SDIS);

        $this->serviceSecoursRouteRepository
            ->expects($this->once())
            ->method('findOneByTerritoryAndCategory')
            ->with($territory, ServiceSecoursCategory::SDIS)
            ->willReturn($expectedRoute);

        $result = $this->resolver->resolve($proConnectUser);

        $this->assertSame($expectedRoute, $result);
    }

    public function testSiretFromProConnectIsTransmittedToSireneService(): void
    {
        $proConnectUser = new ProConnectUser([
            'uid' => '456',
            'sub' => 'sub_456',
            'email' => 'agent@police.fr',
            'siret' => '98765432109876',
            'organizational_unit' => 'Commissariat Central',
        ]);

        $territory = new Territory();
        $expectedRoute = new ServiceSecoursRoute();

        $this->sireneService
            ->expects($this->once())
            ->method('getSiret')
            ->with('98765432109876')
            ->willReturn(new SiretInformation('98765432109876', '75056'));

        $this->zipcodeProvider
            ->method('getTerritoryByInseeCode')
            ->willReturn($territory);

        $this->serviceSecoursCategoryResolver
            ->method('resolve')
            ->willReturn(ServiceSecoursCategory::POLICE);

        $this->serviceSecoursRouteRepository
            ->method('findOneByTerritoryAndCategory')
            ->willReturn($expectedRoute);

        $result = $this->resolver->resolve($proConnectUser);
        $this->assertSame($expectedRoute, $result);
    }

    public function testOrganizationalUnitIsTransmittedToCategoryResolver(): void
    {
        $proConnectUser = new ProConnectUser([
            'uid' => '789',
            'sub' => 'sub_789',
            'email' => 'agent@gendarmerie.interieur.gouv.fr',
            'siret' => '11223344556677',
            'organizational_unit' => 'Gendarmerie Nationale',
        ]);

        $territory = new Territory();
        $expectedRoute = new ServiceSecoursRoute();

        $this->sireneService
            ->method('getSiret')
            ->willReturn(new SiretInformation('11223344556677', '69123'));

        $this->zipcodeProvider
            ->method('getTerritoryByInseeCode')
            ->willReturn($territory);

        $this->serviceSecoursCategoryResolver
            ->expects($this->once())
            ->method('resolve')
            ->with('Gendarmerie Nationale')
            ->willReturn(ServiceSecoursCategory::GENDARMERIE);

        $this->serviceSecoursRouteRepository
            ->method('findOneByTerritoryAndCategory')
            ->willReturn($expectedRoute);

        $result = $this->resolver->resolve($proConnectUser);
        $this->assertSame($expectedRoute, $result);
    }

    #[DataProvider('provideInvalidSirets')]
    public function testResolveFailsWhenSiretIsMissingOrEmpty(?string $siret): void
    {
        $proConnectUser = new ProConnectUser([
            'uid' => '123',
            'sub' => 'sub_123',
            'email' => 'agent@test.fr',
            'siret' => $siret,
            'organizational_unit' => 'SDIS 13',
        ]);

        $this->expectException(ServiceSecoursRouteResolutionException::class);
        $this->expectExceptionMessage('Le SIRET est manquant dans l\'identité ProConnect.');

        $this->resolver->resolve($proConnectUser);
    }

    public static function provideInvalidSirets(): \Generator
    {
        yield 'null' => [null];
        yield 'empty' => [''];
        yield 'spaces' => ['   '];
    }

    #[DataProvider('provideInvalidOrganizationalUnits')]
    public function testResolveFailsWhenOrganizationalUnitIsMissingOrEmpty(?string $organizationalUnit): void
    {
        $proConnectUser = new ProConnectUser([
            'uid' => '123',
            'sub' => 'sub_123',
            'email' => 'agent@test.fr',
            'siret' => '12345678901234',
            'organizational_unit' => $organizationalUnit,
        ]);

        $this->expectException(ServiceSecoursRouteResolutionException::class);
        $this->expectExceptionMessage('L\'unité organisationnelle (organizational_unit) est manquante dans l\'identité ProConnect.');

        $this->resolver->resolve($proConnectUser);
    }

    public static function provideInvalidOrganizationalUnits(): \Generator
    {
        yield 'null' => [null];
        yield 'empty' => [''];
        yield 'spaces' => ['   '];
    }

    public function testResolveFailsWhenTerritoryNotFoundForInseeCode(): void
    {
        $proConnectUser = new ProConnectUser([
            'uid' => '123',
            'sub' => 'sub_123',
            'email' => 'agent@test.fr',
            'siret' => '12345678901234',
            'organizational_unit' => 'SDIS 99',
        ]);

        $this->sireneService
            ->method('getSiret')
            ->willReturn(new SiretInformation('12345678901234', '99999'));

        $this->zipcodeProvider
            ->method('getTerritoryByInseeCode')
            ->with('99999')
            ->willReturn(null);

        $this->expectException(ServiceSecoursRouteResolutionException::class);
        $this->expectExceptionMessage('Aucun territoire trouvé pour le code commune INSEE "99999" (SIRET "12345678901234").');

        $this->resolver->resolve($proConnectUser);
    }

    public function testResolveFailsWhenNoServiceSecoursRouteFoundForTerritoryAndCategory(): void
    {
        $proConnectUser = new ProConnectUser([
            'uid' => '123',
            'sub' => 'sub_123',
            'email' => 'agent@test.fr',
            'siret' => '12345678901234',
            'organizational_unit' => 'SDIS 13',
        ]);

        $territory = new Territory();
        $territory->setName('Bouches-du-Rhône');

        $this->sireneService
            ->method('getSiret')
            ->willReturn(new SiretInformation('12345678901234', '13001'));

        $this->zipcodeProvider
            ->method('getTerritoryByInseeCode')
            ->willReturn($territory);

        $this->serviceSecoursCategoryResolver
            ->method('resolve')
            ->willReturn(ServiceSecoursCategory::SDIS);

        $this->serviceSecoursRouteRepository
            ->method('findOneByTerritoryAndCategory')
            ->with($territory, ServiceSecoursCategory::SDIS)
            ->willReturn(null);

        $this->expectException(ServiceSecoursRouteResolutionException::class);
        $this->expectExceptionMessage('Aucune route Service Secours configurée pour le territoire "Bouches-du-Rhône" et la catégorie "SDIS".');

        $this->resolver->resolve($proConnectUser);
    }
}
