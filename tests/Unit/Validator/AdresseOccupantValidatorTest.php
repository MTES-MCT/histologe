<?php

namespace App\Tests\Unit\Validator;

use App\Dto\ServiceSecours\FormServiceSecoursStep2;
use App\Entity\Territory;
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

    protected function createValidator(): AdresseOccupantValidator
    {
        $this->zipcodeProvider = $this->createMock(ZipcodeProvider::class);

        return new AdresseOccupantValidator($this->zipcodeProvider);
    }

    /**
     * @dataProvider provideInvalidTerritoryCases
     */
    public function testItAddsViolationWhenTerritoryIsInvalid(
        ?Territory $territoryFromInsee,
        ?Territory $territoryFromPostal,
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
                ->willReturn($territoryFromPostal);
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

    public function provideInvalidTerritoryCases(): iterable
    {
        // territoire inactif via INSEE
        $inactiveTerritory = $this->createMock(Territory::class);
        $inactiveTerritory->method('isIsActive')->willReturn(false);

        yield 'territory inactive via insee' => [
            $inactiveTerritory,
            null,
            (new AdresseOccupant())->messageInsee,
            '13055',
            false,
        ];

        yield 'no territory found' => [
            null,
            null,
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

        $this->zipcodeProvider
            ->expects($this->once())
            ->method('getTerritoryByInseeCode')
            ->with('13055')
            ->willReturn($territory);

        $this->validator->validate($form, $constraint);

        $this
            ->buildViolation($constraint->messageTerritoryMismatch)
            ->atPath('property.path.adresseCompleteOccupant')
            ->assertRaised();
    }
}
