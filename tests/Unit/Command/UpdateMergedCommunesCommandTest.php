<?php

namespace App\Tests\Unit\Command;

use App\Command\UpdateMergedCommunesCommand;
use App\Entity\Commune;
use App\Repository\CommuneRepository;
use App\Service\Import\CsvParser;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateMergedCommunesCommandTest extends TestCase
{
    private MockObject&EntityManagerInterface $entityManager;
    private MockObject&CsvParser $csvParser;
    private MockObject&CommuneRepository $communeRepository;
    private UpdateMergedCommunesCommand $command;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->csvParser = $this->createMock(CsvParser::class);
        $this->communeRepository = $this->createMock(CommuneRepository::class);

        $this->command = new UpdateMergedCommunesCommand(
            $this->entityManager,
            $this->csvParser,
            $this->communeRepository,
            'https://example.com/communes.csv'
        );
    }

    public function testProcessCsvRowWithEmptyFields(): void
    {
        $this->communeRepository
            ->expects($this->never())
            ->method('findBy');

        $nbDeprecatedProperty = new \ReflectionProperty($this->command, 'nbDeprecated');
        $nbRenamedProperty = new \ReflectionProperty($this->command, 'nbRenamed');

        // Test avec newCommuneInsee vide
        $this->command->processCsvRow([null, '', 'Patate', '75056']);
        $this->assertEquals(0, $nbDeprecatedProperty->getValue($this->command));
        $this->assertEquals(0, $nbRenamedProperty->getValue($this->command));

        // Test avec newCommuneName vide
        $this->command->processCsvRow([null, '75056', '', '75001']);
        $this->assertEquals(0, $nbDeprecatedProperty->getValue($this->command));
        $this->assertEquals(0, $nbRenamedProperty->getValue($this->command));

        // Test avec oldCommuneInsee vide
        $this->command->processCsvRow([null, '75056', 'Patate', '']);
        $this->assertEquals(0, $nbDeprecatedProperty->getValue($this->command));
        $this->assertEquals(0, $nbRenamedProperty->getValue($this->command));
    }

    public function testProcessCsvRowDeprecatesOldCommune(): void
    {
        $oldCommune = $this->createMock(Commune::class);
        $oldCommune->method('getNom')->willReturn('Les Touches');
        $oldCommune->method('getCommuneMergedInto')->willReturn(null);
        $newCommune = $this->createMock(Commune::class);
        $newCommune->method('getNom')->willReturn('Nort-sur-Erdre');
        $oldCommune->expects($this->once())->method('setCommuneMergedInto')->with($newCommune);

        $this->communeRepository
            ->method('findBy')
            ->willReturnOnConsecutiveCalls(
                [$oldCommune], // Pour l'ancien code Insee
                []             // Pour le nouveau code Insee
            );

        $this->communeRepository->method('findOneBy')->willReturn($newCommune);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($oldCommune);

        // Simuler SymfonyStyle pour éviter l'erreur lors de l'appel à info()
        $ioProperty = new \ReflectionProperty($this->command, 'io');
        $ioProperty->setValue($this->command, $this->createMock(SymfonyStyle::class));

        $this->command->processCsvRow([null, '44110', 'Nort-sur-Erdre', '44205']);

        $nbDeprecatedProperty = new \ReflectionProperty($this->command, 'nbDeprecated');
        $nbRenamedProperty = new \ReflectionProperty($this->command, 'nbRenamed');
        $this->assertEquals(1, $nbDeprecatedProperty->getValue($this->command));
        $this->assertEquals(0, $nbRenamedProperty->getValue($this->command));
    }

    public function testProcessCsvRowDoesNotDeprecateIfAlreadyDeprecated(): void
    {
        $oldCommune = $this->createMock(Commune::class);
        $oldCommune->method('getNom')->willReturn('Les Touches');
        $oldCommune->method('getCommuneMergedInto')->willReturn($this->createMock(Commune::class));
        $oldCommune->expects($this->never())->method('setCommuneMergedInto');

        $this->communeRepository
            ->expects($this->once())
            ->method('findBy')
            ->willReturnOnConsecutiveCalls(
                [$oldCommune], // Pour l'ancien code Insee
                []             // Pour le nouveau code Insee
            );

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $ioProperty = new \ReflectionProperty($this->command, 'io');
        $ioProperty->setValue($this->command, $this->createMock(SymfonyStyle::class));

        $this->command->processCsvRow([null, '44205', 'Nort-sur-Erdre', '44110']);

        $nbDeprecatedProperty = new \ReflectionProperty($this->command, 'nbDeprecated');
        $nbRenamedProperty = new \ReflectionProperty($this->command, 'nbRenamed');
        $this->assertEquals(0, $nbDeprecatedProperty->getValue($this->command));
        $this->assertEquals(0, $nbRenamedProperty->getValue($this->command));
    }

    public function testProcessCsvRowDoesNotDeprecateIfSameName(): void
    {
        $oldCommune = $this->createMock(Commune::class);
        $oldCommune->method('getNom')->willReturn('Les Touches');
        $oldCommune->expects($this->never())->method('setCommuneMergedInto');

        $this->communeRepository
            ->expects($this->once())
            ->method('findBy')
            ->willReturnOnConsecutiveCalls(
                [$oldCommune], // Pour l'ancien code Insee
                []             // Pour le nouveau code Insee
            );

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $ioProperty = new \ReflectionProperty($this->command, 'io');
        $ioProperty->setValue($this->command, $this->createMock(SymfonyStyle::class));

        $this->command->processCsvRow([null, '44110', 'Nort-sur-Erdre', '44205']);

        $nbDeprecatedProperty = new \ReflectionProperty($this->command, 'nbDeprecated');
        $nbRenamedProperty = new \ReflectionProperty($this->command, 'nbRenamed');
        $this->assertEquals(0, $nbDeprecatedProperty->getValue($this->command));
        $this->assertEquals(1, $nbRenamedProperty->getValue($this->command));
    }

    public function testProcessCsvRowRenamesNewCommune(): void
    {
        $newCommune = $this->createMock(Commune::class);
        $newCommune->method('getNom')->willReturn('Nort-sur-Erdre');
        $newCommune->expects($this->once())->method('setNom')->with('Nort-sur-Erdros');

        $this->communeRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['codeInsee' => '44110'])
            ->willReturn([$newCommune]);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($newCommune);

        $ioProperty = new \ReflectionProperty($this->command, 'io');
        $ioProperty->setValue($this->command, $this->createMock(SymfonyStyle::class));

        $this->command->processCsvRow([null, '44110', 'Nort-sur-Erdros', '44110']);

        $nbDeprecatedProperty = new \ReflectionProperty($this->command, 'nbDeprecated');
        $nbRenamedProperty = new \ReflectionProperty($this->command, 'nbRenamed');
        $this->assertEquals(0, $nbDeprecatedProperty->getValue($this->command));
        $this->assertEquals(1, $nbRenamedProperty->getValue($this->command));
    }

    public function testProcessCsvRowDoesNotRenameIfSameName(): void
    {
        $newCommune = $this->createMock(Commune::class);
        $newCommune->method('getNom')->willReturn('Nort-sur-Erdre');
        $newCommune->expects($this->never())->method('setNom');

        $this->communeRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['codeInsee' => '44110'])
            ->willReturn([$newCommune]);

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $ioProperty = new \ReflectionProperty($this->command, 'io');
        $ioProperty->setValue($this->command, $this->createMock(SymfonyStyle::class));

        $this->command->processCsvRow([null, '44110', 'Nort-sur-Erdre', '44110']);

        $nbDeprecatedProperty = new \ReflectionProperty($this->command, 'nbDeprecated');
        $nbRenamedProperty = new \ReflectionProperty($this->command, 'nbRenamed');
        $this->assertEquals(0, $nbDeprecatedProperty->getValue($this->command));
        $this->assertEquals(0, $nbRenamedProperty->getValue($this->command));
    }

    public function testProcessCsvRowCleansInseeCode(): void
    {
        // Test que les codes INSEE à 4 chiffres sont bien complétés avec un 0
        $this->communeRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['codeInsee' => '05678']) // Vérifie que '5678' devient '05678'
            ->willReturn([]);

        $ioProperty = new \ReflectionProperty($this->command, 'io');
        $ioProperty->setValue($this->command, $this->createMock(SymfonyStyle::class));

        // Test avec un code Insee à 4 chiffres pour newCommuneInsee et oldCommuneInsee
        $this->command->processCsvRow([null, '1234', 'Test', '5678']);

        $nbDeprecatedProperty = new \ReflectionProperty($this->command, 'nbDeprecated');
        $nbRenamedProperty = new \ReflectionProperty($this->command, 'nbRenamed');
        $this->assertEquals(0, $nbDeprecatedProperty->getValue($this->command));
        $this->assertEquals(0, $nbRenamedProperty->getValue($this->command));
    }

    public function testProcessCsvRowCleansCommuneName(): void
    {
        $newCommune = $this->createMock(Commune::class);
        $newCommune->method('getNom')->willReturn('Test');
        $newCommune->expects($this->once())->method('setNom')->with('Test Commune');

        $this->communeRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['codeInsee' => '44205'])
            ->willReturn([$newCommune]);

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $ioProperty = new \ReflectionProperty($this->command, 'io');
        $ioProperty->setValue($this->command, $this->createMock(SymfonyStyle::class));

        // Test avec des espaces multiples et des * - codes INSEE identiques pour tester le renommage
        $this->command->processCsvRow([null, '44205', '  Test*  Commune  ', '44205']);

        $nbDeprecatedProperty = new \ReflectionProperty($this->command, 'nbDeprecated');
        $nbRenamedProperty = new \ReflectionProperty($this->command, 'nbRenamed');
        $this->assertEquals(0, $nbDeprecatedProperty->getValue($this->command));
        $this->assertEquals(1, $nbRenamedProperty->getValue($this->command));
    }

    public function testProcessCsvRowHandlesMultipleCommunesWithSameInsee(): void
    {
        // Teste la dépréciation de multiples communes avec le même code INSEE ancien
        $newCommune = $this->createMock(Commune::class);
        $newCommune->method('getNom')->willReturn('Nouvelle Commune');

        $oldCommune1 = $this->createMock(Commune::class);
        $oldCommune1->method('getNom')->willReturn('Ancienne Commune 1');
        $oldCommune1->method('getCommuneMergedInto')->willReturn(null);
        $oldCommune1->expects($this->never())->method('setCommuneMergedInto');

        $oldCommune2 = $this->createMock(Commune::class);
        $oldCommune2->method('getNom')->willReturn('Ancienne Commune 2');
        $oldCommune2->method('getCommuneMergedInto')->willReturn(null);
        $oldCommune2->expects($this->never())->method('setCommuneMergedInto');

        $this->communeRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['codeInsee' => '44999'])
            ->willReturn([$oldCommune1, $oldCommune2]);

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $ioProperty = new \ReflectionProperty($this->command, 'io');
        $ioProperty->setValue($this->command, $this->createMock(SymfonyStyle::class));

        $this->command->processCsvRow([null, '44205', 'Nouvelle Commune', '44999']);

        $nbDeprecatedProperty = new \ReflectionProperty($this->command, 'nbDeprecated');
        $nbRenamedProperty = new \ReflectionProperty($this->command, 'nbRenamed');
        $this->assertEquals(0, $nbDeprecatedProperty->getValue($this->command));
        $this->assertEquals(1, $nbRenamedProperty->getValue($this->command));
    }
}
