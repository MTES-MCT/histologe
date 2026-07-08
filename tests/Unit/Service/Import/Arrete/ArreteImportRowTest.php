<?php

namespace App\Tests\Unit\Service\Import\Arrete;

use App\Service\Import\Arrete\ArreteImportRow;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ArreteImportRowTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    #[DataProvider('provideRowData')]
    public function testValidation(ArreteImportRow $row, int $expectedViolationCount, ?string $propertyPath = null): void
    {
        $violations = $this->validator->validate($row);
        $this->assertCount($expectedViolationCount, $violations);
        if ($expectedViolationCount > 0 && $propertyPath) {
            $this->assertEquals($propertyPath, $violations[0]->getPropertyPath());
        }
    }

    public static function provideRowData(): \Generator
    {
        $tomorrow = new \DateTimeImmutable('+1 day')->format('d/m/Y');
        $today = new \DateTimeImmutable('today');

        yield 'Valid row' => [
            new ArreteImportRow()
                ->setDateArrete('01/01/2023')
                ->setDateArreteMainLevee('02/01/2023')
                ->setClassificationArrete('Insalubrité')
                ->setNumeroVoie('1')
                ->setNomVoie('Quai de la joliette')
                ->setCodePostal('13012')
                ->setCommune('Marseille')
                ->setIdentifiantParcellaire('123456'),
            0,
        ];

        yield 'Invalid dateArrete in future' => [
            new ArreteImportRow()
                ->setDateArrete($tomorrow)
                ->setClassificationArrete('Insalubrité')
                ->setNumeroVoie('12')
                ->setNomVoie('Rue Désirée Clary')
                ->setCodePostal('13002')
                ->setCommune('Marseille')
                ->setIdentifiantParcellaire('1234567'),
            1,
            'dateArrete',
        ];

        yield 'Invalid dateArreteMainLevee in future' => [
            new ArreteImportRow()
                ->setDateArrete('01/01/2020')
                ->setDateArreteMainLevee($tomorrow)
                ->setClassificationArrete('Insalubrité')
                ->setNomVoie('Square de la rouguière')
                ->setCodePostal('13011')
                ->setCommune('Marseille')
                ->setIdentifiantParcellaire('12345678'),
            1,
            'dateArreteMainLevee',
        ];

        yield 'Invalid classification' => [
            new ArreteImportRow()
                ->setDateArrete($today)
                ->setClassificationArrete('Classification Inconnue')
                ->setNumeroVoie('17')
                ->setNomVoie('Boulevard Michelet')
                ->setCodePostal('13008')
                ->setCommune('Marseille')
                ->setIdentifiantParcellaire('123456910'),
            1,
            'classificationArrete',
        ];

        yield 'Empty required fields' => [
            new ArreteImportRow(),
            6, // dateArrete, classificationArrete, nomVoie, codePostal, commune, identifiantParcellaire
        ];

        yield 'Invalid dateArreteMainLevee (before dateArrete)' => [
            new ArreteImportRow()
                ->setDateArrete('02/01/2023')
                ->setDateArreteMainLevee('01/01/2023')
                ->setClassificationArrete('Insalubrité')
                ->setNomVoie('Rue de la Paix')
                ->setCodePostal('75002')
                ->setCommune('Paris')
                ->setIdentifiantParcellaire('12345'),
            1,
            'dateArreteMainLevee',
        ];

        yield 'Invalid dateArreteMainLevee (same as dateArrete)' => [
            new ArreteImportRow()
                ->setDateArrete('01/01/2023')
                ->setDateArreteMainLevee('01/01/2023')
                ->setClassificationArrete('Insalubrité')
                ->setNomVoie('Rue de la Paix')
                ->setCodePostal('75002')
                ->setCommune('Paris')
                ->setIdentifiantParcellaire('12345'),
            1,
            'dateArreteMainLevee',
        ];

        yield 'Valid dateArreteMainLevee (after dateArrete)' => [
            new ArreteImportRow()
                ->setDateArrete('01/01/2023')
                ->setDateArreteMainLevee('02/01/2023')
                ->setClassificationArrete('Insalubrité')
                ->setNomVoie('Rue de la Paix')
                ->setCodePostal('75002')
                ->setCommune('Paris')
                ->setIdentifiantParcellaire('12345'),
            0,
        ];
    }
}
