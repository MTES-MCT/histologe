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

    public function testItAddsViolationWhenTerritoryInactive(): void
    {
        $territoryFromInsee = $this->createMock(Territory::class);
        $constraint = new AdresseOccupant();

        $form = new FormServiceSecoursStep2();
        $form->adresseCompleteOccupant = '19 Quai de la Joliette, 13002 Marseille';
        $form->inseeOccupant = '13055';

        $this->zipcodeProvider
            ->expects($this->once())
            ->method('getTerritoryByInseeCode')
            ->with('13055')
            ->willReturn($territoryFromInsee);

        $this->zipcodeProvider
            ->expects($this->never())
            ->method('getTerritoryByPostalCode');

        $this->validator->validate($form, $constraint);

        $this
            ->buildViolation($constraint->messageInsee)
            ->setParameter('{{ code }}', '13055')
            ->atPath('property.path.adresseCompleteOccupant')
            ->assertRaised();
    }

    public function testItAddsViolationWhenTerritoryNotFound(): void
    {
        $constraint = new AdresseOccupant();

        $form = new FormServiceSecoursStep2();
        $form->adresseCompleteOccupant = '19 Quai de la Joliette, 13002 Marseille';
        $form->adresseOccupant = '19 Quai de la Joliette';
        $form->cpOccupant = '13002';
        $form->villeOccupant = 'Marseille';
        $form->rnbId = '13055';
        $form->inseeOccupant = '13055';

        $this->zipcodeProvider
            ->expects($this->once())
            ->method('getTerritoryByInseeCode')
            ->with('13055')
            ->willReturn(null);

        $this->zipcodeProvider
            ->expects($this->once())
            ->method('getTerritoryByPostalCode')
            ->with('13002')
            ->willReturn(null);

        $this->validator->validate($form, $constraint);

        $this
            ->buildViolation($constraint->messagePostalCode)
            ->setParameter('{{ code }}', '13002')
            ->atPath('property.path.adresseCompleteOccupant')
            ->assertRaised();
    }

    public function testItAddsViolationWhenExpectedTerritoryZipDoesNotMatch(): void
    {
        $constraint = new AdresseOccupant();

        $form = new FormServiceSecoursStep2();
        $form->adresseCompleteOccupant = '19 Quai de la Joliette, 13002 Marseille';
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

        $territory->method('getZip')->willReturn('13002');
        $this->validator->validate($form, $constraint);

        $this
            ->buildViolation($constraint->messageTerritoryMismatch)
            ->setParameter('{{ territory }}', '75001 Paris')
            ->atPath('property.path.adresseCompleteOccupant')
            ->assertRaised();
    }

    public function testItAddsViolationWheBatimentNotSelected(): void
    {
        $constraint = new AdresseOccupant();

        $form = new FormServiceSecoursStep2();
        $form->adresseCompleteOccupant = '19 Quai de la Joliette, 13002 Marseille';
        $form->adresseOccupant = '19 Quai de la Joliette';
        $form->cpOccupant = '13002';
        $form->villeOccupant = 'Marseille';
        $form->inseeOccupant = '13055';

        $territory = $this->createMock(Territory::class);
        $territory->method('isIsActive')->willReturn(true);
        $territory->method('getZip')->willReturn('13002');

        $this->zipcodeProvider
            ->expects($this->never())
            ->method('getTerritoryByInseeCode');

        $this->zipcodeProvider
            ->expects($this->never())
            ->method('getTerritoryByPostalCode');

        $this->validator->validate($form, $constraint);

        $this
            ->buildViolation($constraint->messageRnbId)
            ->atPath('property.path.adresseCompleteOccupant')
            ->assertRaised();
    }
}
