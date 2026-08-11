<?php

namespace App\Tests\Unit\Factory;

use App\Entity\Address;
use App\Entity\Arrete;
use App\Entity\Territory;
use App\Entity\User;
use App\Factory\AddressFactory;
use App\Factory\ArreteFactory;
use App\Repository\AddressRepository;
use App\Service\Gouv\Ban\AddressService;
use App\Service\Gouv\Rnb\RnbService;
use App\Service\Import\Arrete\ArreteImportRow;
use App\Service\Signalement\ZipcodeProvider;
use App\Tests\Fake\AddressServiceFake;
use App\Tests\Fake\RnbServiceFake;
use App\Tests\FixturesHelper;
use LongitudeOne\Spatial\Exception\InvalidValueException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ArreteFactoryTest extends TestCase
{
    use FixturesHelper;

    private AddressService $addressService;
    private RnbService $rnbService;
    private MockObject|AddressRepository $addressRepository;
    private MockObject|ZipcodeProvider $zipcodeProvider;
    private ArreteFactory $arreteFactory;
    private MockObject|AddressFactory $addressFactory;

    protected function setUp(): void
    {
        $this->addressService = new AddressServiceFake();
        $this->rnbService = new RnbServiceFake();
        $this->addressRepository = $this->createMock(AddressRepository::class);
        $this->zipcodeProvider = $this->createMock(ZipcodeProvider::class);
        $this->addressFactory = $this->createMock(AddressFactory::class);

        $this->arreteFactory = new ArreteFactory(
            $this->addressService,
            $this->addressRepository,
            $this->addressFactory
        );
    }

    /**
     * @throws \ReflectionException
     * @throws InvalidValueException
     * @throws InvalidValueException
     */
    #[DataProvider('provideArreteData')]
    public function testCreateInstanceFrom(
        ArreteImportRow $arreteImportRow,
        User|string|null $user,
        ?Address $existingAddressByBanId,
        ?Address $existingAddressByCriteria,
        Territory|string|null $territory,
        bool $shouldReturnArrete,
        ?string $expectedType = null,
    ): void {
        $territory = $this->getTerritory(name: 'Loire-Atlantique', zip: '44');
        $reflection = new \ReflectionClass($territory);
        $property = $reflection->getProperty('id');
        $property->setValue($territory, 44);
        $existingAddressByBanId?->setTerritory($territory);
        $existingAddressByCriteria?->setTerritory($territory);
        /** @var User|null $currentUser */
        $currentUser = null;
        if ('admin_user_diff_territory' === $user) {
            /** @var MockObject&User $userMock */
            $userMock = $this->createMock(User::class);
            $userMock->expects($this->any())->method('isTerritoryAdmin')->willReturn(true);
            $userTerritory = $this->getTerritory(name: 'Autre Territoire', zip: '13');
            $reflectionUserTerritory = new \ReflectionClass($userTerritory);
            $propertyUserTerritory = $reflectionUserTerritory->getProperty('id');
            $propertyUserTerritory->setValue($userTerritory, 13);
            $userMock->expects($this->any())->method('getFirstTerritory')->willReturn($userTerritory);
            $currentUser = $userMock;
        } elseif ($user instanceof User) {
            $currentUser = $user;
        }

        if ($this->addressService->getAcceptableBanAddress($arreteImportRow->getAddress())) {
            /** @var MockObject&AddressRepository $addressRepository */
            $addressRepository = $this->addressRepository;
            $addressRepository->expects($this->any())->method('findOneBy')
                ->willReturnCallback(static function ($criteria) use ($existingAddressByBanId, $existingAddressByCriteria) {
                    if (isset($criteria['banId'])) {
                        return $existingAddressByBanId;
                    }

                    return $existingAddressByCriteria;
                });

            /** @var MockObject&ZipcodeProvider $zipcodeProvider */
            $zipcodeProvider = $this->zipcodeProvider;
            $zipcodeProvider->expects($this->any())->method('getTerritoryByInseeCode')
                ->willReturn($territory);
        }

        $arrete = $this->arreteFactory->createInstanceFrom($arreteImportRow, $currentUser);

        if ($shouldReturnArrete) {
            $this->assertInstanceOf(Arrete::class, $arrete);
            $this->assertEquals($arreteImportRow->getDateArrete(), $arrete->getDateArrete());
            if ($expectedType) {
                $this->assertEquals($expectedType, $arrete->getArreteType()->name);
            }
            if ($arreteImportRow->getDateArreteMainLevee()) {
                $this->assertEquals($arreteImportRow->getDateArreteMainLevee(), $arrete->getDateMainLevee());
            }
        } else {
            $this->assertNull($arrete);
        }
    }

    /**
     * @throws \ReflectionException
     */
    public static function provideArreteData(): \Generator
    {
        $importRow = new ArreteImportRow()
            ->setDateArrete(new \DateTimeImmutable('2023-01-01'))
            ->setClassificationArrete('Insalubrité')
            ->setNumeroVoie('8')
            ->setNomVoie('Rue de la tourmentinerie')
            ->setCodePostal('44850')
            ->setCommune('Saint-Mars-du-Désert')
            ->setIdentifiantParcellaire('12345');

        yield 'Score BAN trop faible' => [
            (clone $importRow)
                ->setNumeroVoie(null)
                ->setNomVoie('Chemin du grand méchant loup')
                ->setCodePostal('30360')
                ->setCommune('Vézénobres'),
            new User(),
            null,
            null,
            null,
            false,
        ];

        yield 'Score BAN trop faible avec rnbId' => [
            (clone $importRow)
                ->setNumeroVoie(null)
                ->setNomVoie('Chemin du grand méchant loup')
                ->setCodePostal('30360')
                ->setCommune('Vézénobres')
                ->setRnbId('Y1QC6FM9XXGS'),
            (new User())->setRoles(['ROLE_ADMIN']),
            null,
            null,
            'territory_30',
            true,
            'ARRETE_L_511_19_INSALUBRITE',
        ];

        yield 'Adresse trouvée par banId' => [
            $importRow,
            new User()->setRoles(['ROLE_ADMIN']),
            new Address()->setBanId('2ac4d3cd-67ee-46d4-9b5f-207bc6143aab'),
            null,
            'territory_44',
            true,
            'ARRETE_L_511_19_INSALUBRITE',
        ];

        yield 'Adresse trouvée par critères' => [
            $importRow,
            new User()->setRoles(['ROLE_ADMIN']),
            null,
            new Address()->setStreet('Rue de la tourmentinerie'),
            'territory_44',
            true,
            'ARRETE_L_511_19_INSALUBRITE',
        ];

        yield 'Nouvelle adresse créée avec succès' => [
            $importRow,
            (new User())->setRoles(['ROLE_ADMIN']),
            null,
            null,
            'territory_44',
            true,
            'ARRETE_L_511_19_INSALUBRITE',
        ];

        yield 'Admin territoire différent' => [
            $importRow,
            'admin_user_diff_territory',
            null,
            null,
            'territory_44',
            false,
        ];

        $importRowWithMainLevee = clone $importRow;
        $importRowWithMainLevee->setDateArreteMainLevee(new \DateTimeImmutable('2023-02-01'));

        yield 'Avec date de main levée' => [
            $importRowWithMainLevee,
            new User()->setRoles(['ROLE_ADMIN']),
            null,
            new Address()->setStreet('Rue de la tourmentinerie'),
            'territory_44',
            true,
            'ARRETE_L_511_19_INSALUBRITE',
        ];
    }
}
