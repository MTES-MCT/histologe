<?php

namespace App\Tests\Unit\Validator;

use App\Dto\ServiceSecours\FormServiceSecoursStep2;
use App\Entity\Territory;
use App\Repository\TerritoryRepository;
use App\Service\Signalement\ZipcodeProvider;
use App\Validator\AdresseOccupant;
use App\Validator\AdresseOccupantValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<AdresseOccupantValidator>
 */
class AdresseOccupantValidatorTest extends ConstraintValidatorTestCase
{
    private ZipcodeProvider&MockObject $zipcodeProvider;
    private TerritoryRepository&MockObject $territoryRepository;

    protected function createValidator(): AdresseOccupantValidator
    {
        $this->zipcodeProvider = $this->createMock(ZipcodeProvider::class);
        $this->territoryRepository = $this->createMock(TerritoryRepository::class);

        return new AdresseOccupantValidator($this->zipcodeProvider, $this->territoryRepository);
    }

    /**
     * @dataProvider provideInvalidTerritoryCases
     */
    public function testItAddsViolationWhenTerritoryIsInvalid(
        bool $isInactiveTerritory,
        bool $hasTerritoryFromInsee,
        string $expectedMessage,
        string $expectedCode,
        bool $expectPostalFallback,
    ): void {
        $constraint = new AdresseOccupant();

        $form = new FormServiceSecoursStep2();
        $form->adresseCompleteOccupant = '19 Quai de la Joliette, 13002 Marseille';
        $form->adresseOccupant = '19 Quai de la Joliette';
        $form->cpOccupant = '13002';
        $form->villeOccupant = 'Marseille';
        $form->inseeOccupant = '13055';

        $territoryFromInsee = null;
        if ($hasTerritoryFromInsee) {
            $territoryFromInsee = $this->createMock(Territory::class);
            $territoryFromInsee->method('isIsActive')->willReturn(!$isInactiveTerritory);
        }

        $this->zipcodeProvider
            ->expects($this->once())
            ->method('getTerritoryByInseeCode')
            ->with('13055')
            ->willReturn($territoryFromInsee);

        if ($expectPostalFallback) {
            $this->zipcodeProvider
                ->expects($this->once())
                ->method('getTerritoryByPostalCode')
                ->with('13002')
                ->willReturn(null);
        } else {
            $this->zipcodeProvider
                ->expects($this->never())
                ->method('getTerritoryByPostalCode');
        }

        $this->validator->validate($form, $constraint);

        $this
            ->buildViolation($expectedMessage)
            ->setParameter('{{ code }}', $expectedCode)
            ->atPath('property.path.adresseCompleteOccupant')
            ->assertRaised();
    }

    public static function provideInvalidTerritoryCases(): iterable
    {
        yield 'territory inactive via insee' => [
            true,   // isInactiveTerritory
            true,   // hasTerritoryFromInsee
            (new AdresseOccupant())->messageInsee,
            '13055',
            false,
        ];

        yield 'no territory found' => [
            false,  // isInactiveTerritory
            false,  // hasTerritoryFromInsee
            (new AdresseOccupant())->messagePostalCode,
            '13002',
            true,
        ];
    }

    public function testItAddsViolationWhenExpectedTerritoryZipDoesNotMatch(): void
    {
        $constraint = new AdresseOccupant();

        $form = new FormServiceSecoursStep2();
        $form->adresseCompleteOccupant = '19 Quai de la Joliette, 13002 Marseille';
        $form->adresseOccupant = '19 Quai de la Joliette';
        $form->cpOccupant = '13002';
        $form->villeOccupant = 'Marseille';
        $form->inseeOccupant = '13055';
        $form->territoryZip = '75001';

        $territory = $this->createMock(Territory::class);
        $territory->method('isIsActive')->willReturn(true);
        $territory->method('getZip')->willReturn('13002');

        $expectedTerritory = $this->createMock(Territory::class);
        $expectedTerritory->method('getZip')->willReturn('75001');
        $expectedTerritory->method('getZipAndName')->willReturn('75001 Paris');

        $this->zipcodeProvider
            ->expects($this->once())
            ->method('getTerritoryByInseeCode')
            ->with('13055')
            ->willReturn($territory);

        $this->zipcodeProvider
            ->expects($this->never())
            ->method('getTerritoryByPostalCode');

        $this->territoryRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['zip' => '75001'])
            ->willReturn($expectedTerritory);

        $this->validator->validate($form, $constraint);

        $this
            ->buildViolation($constraint->messageTerritoryMismatch)
            ->setParameter('{{ territory }}', '75001 Paris')
            ->atPath('property.path.adresseCompleteOccupant')
            ->assertRaised();
    }
}
