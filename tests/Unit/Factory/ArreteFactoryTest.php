<?php

namespace App\Tests\Unit\Factory;

use App\Entity\Address;
use App\Entity\Arrete;
use App\Entity\Territory;
use App\Entity\User;
use App\Factory\ArreteFactory;
use App\Repository\AddressRepository;
use App\Service\Gouv\Ban\AddressService;
use App\Service\Import\Arrete\ArreteImportRow;
use App\Service\Signalement\ZipcodeProvider;
use App\Tests\Fake\AddressServiceFake;
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
    private MockObject|AddressRepository $addressRepository;
    private MockObject|ZipcodeProvider $zipcodeProvider;
    private ArreteFactory $arreteFactory;

    protected function setUp(): void
    {
        $this->addressService = new AddressServiceFake();
        $this->addressRepository = $this->createMock(AddressRepository::class);
        $this->zipcodeProvider = $this->createMock(ZipcodeProvider::class);

        $this->arreteFactory = new ArreteFactory(
            $this->addressService,
            $this->addressRepository,
            $this->zipcodeProvider
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
        if ('territory_44' === $territory) {
            $territory = $this->getTerritory(name: 'Loire-Atlantique', zip: '44');
            $reflection = new \ReflectionClass($territory);
            $property = $reflection->getProperty('id');
            $property->setValue($territory, 44);
        }
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

        $addressResponse = $this->addressService->getAddress($arreteImportRow->getAddress());

        if ($addressResponse->getScore() >= AddressService::SCORE_IF_BAN_ID_ACCEPTED) {
            /** @var MockObject&AddressRepository $addressRepository */
            $addressRepository = $this->addressRepository;
            $addressRepository->expects($this->any())->method('findOneBy')
                ->willReturnCallback(static function ($criteria) use ($existingAddressByBanId, $existingAddressByCriteria) {
                    if (isset($criteria['banId'])) {
                        return $existingAddressByBanId;
                    }

                    return $existingAddressByCriteria;
                });

            if (!$existingAddressByBanId && !$existingAddressByCriteria) {
                /** @var MockObject&ZipcodeProvider $zipcodeProvider */
                $zipcodeProvider = $this->zipcodeProvider;
                $zipcodeProvider->expects($this->any())->method('getTerritoryByInseeCode')
                    ->willReturn($territory);
            }
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

        yield 'Adresse trouvée par banId' => [
            $importRow,
            new User(),
            new Address()->setBanId('2ac4d3cd-67ee-46d4-9b5f-207bc6143aab'),
            null,
            null,
            true,
            'ARRETE_L_511_19_INSALUBRITE',
        ];

        yield 'Adresse trouvée par critères' => [
            $importRow,
            new User(),
            null,
            new Address()->setStreet('Rue de la tourmentinerie'),
            null,
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

        yield 'Territoire non trouvé' => [
            $importRow,
            new User(),
            null,
            null,
            null,
            false,
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
            new User(),
            null,
            new Address()->setStreet('Rue de la tourmentinerie'),
            null,
            true,
            'ARRETE_L_511_19_INSALUBRITE',
        ];
    }
}
