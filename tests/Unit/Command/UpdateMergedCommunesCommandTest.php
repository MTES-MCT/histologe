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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UpdateMergedCommunesCommandTest extends TestCase
{
    private MockObject&ParameterBagInterface $parameterBag;
    private MockObject&EntityManagerInterface $entityManager;
    private MockObject&CsvParser $csvParser;
    private MockObject&CommuneRepository $communeRepository;
    private UpdateMergedCommunesCommand $command;

    protected function setUp(): void
    {
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->csvParser = $this->createMock(CsvParser::class);
        $this->communeRepository = $this->createMock(CommuneRepository::class);

        $this->command = new UpdateMergedCommunesCommand(
            $this->parameterBag,
            $this->entityManager,
            $this->csvParser,
            $this->communeRepository
        );
    }

    public function testProcessCsvRowWithEmptyFields(): void
    {
        $this->communeRepository
            ->expects($this->never())
            ->method('findBy');

        // Test avec newCommuneInsee vide
        $result = $this->command->processCsvRow([null, '', 'Patate', '75056']);
        $this->assertEquals(['deprecated' => 0, 'renamed' => 0], $result);

        // Test avec newCommuneName vide
        $result = $this->command->processCsvRow([null, '75056', '', '75001']);
        $this->assertEquals(['deprecated' => 0, 'renamed' => 0], $result);

        // Test avec oldCommuneInsee vide
        $result = $this->command->processCsvRow([null, '75056', 'Patate', '']);
        $this->assertEquals(['deprecated' => 0, 'renamed' => 0], $result);
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
        $reflectionProperty = new \ReflectionProperty($this->command, 'io');
        $reflectionProperty->setValue($this->command, $this->createMock(SymfonyStyle::class));

        $result = $this->command->processCsvRow([null, '44110', 'Nort-sur-Erdre', '44205']);
        $this->assertEquals(['deprecated' => 1, 'renamed' => 0], $result);
    }

    public function testProcessCsvRowDoesNotDeprecateIfAlreadyDeprecated(): void
    {
        $oldCommune = $this->createMock(Commune::class);
        $oldCommune->method('getNom')->willReturn('Les Touches');
        $oldCommune->method('getCommuneMergedInto')->willReturn($this->createMock(Commune::class));
        $oldCommune->expects($this->never())->method('setCommuneMergedInto');

        $this->communeRepository
            ->expects($this->exactly(2))
            ->method('findBy')
            ->willReturnOnConsecutiveCalls(
                [$oldCommune], // Pour l'ancien code Insee
                []             // Pour le nouveau code Insee
            );

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $reflectionProperty = new \ReflectionProperty($this->command, 'io');
        $reflectionProperty->setValue($this->command, $this->createMock(SymfonyStyle::class));

        $result = $this->command->processCsvRow([null, '44205', 'Nort-sur-Erdre', '44110']);
        $this->assertEquals(['deprecated' => 0, 'renamed' => 0], $result);
    }

    public function testProcessCsvRowDoesNotDeprecateIfSameName(): void
    {
        $oldCommune = $this->createMock(Commune::class);
        $oldCommune->method('getNom')->willReturn('Les Touches');
        $oldCommune->expects($this->never())->method('setCommuneMergedInto');

        $this->communeRepository
            ->expects($this->exactly(2))
            ->method('findBy')
            ->willReturnOnConsecutiveCalls(
                [$oldCommune], // Pour l'ancien code Insee
                []             // Pour le nouveau code Insee
            );

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $reflectionProperty = new \ReflectionProperty($this->command, 'io');
        $reflectionProperty->setValue($this->command, $this->createMock(SymfonyStyle::class));

        $result = $this->command->processCsvRow([null, '44110', 'Nort-sur-Erdre', '44205']);
        $this->assertEquals(['deprecated' => 0, 'renamed' => 0], $result);
    }

    public function testProcessCsvRowRenamesNewCommune(): void
    {
        $newCommune = $this->createMock(Commune::class);
        $newCommune->method('getNom')->willReturn('Nort-sur-Erdre');
        $newCommune->expects($this->once())->method('setNom')->with('Nort-sur-Erdros');

        $this->communeRepository
            ->expects($this->exactly(2))
            ->method('findBy')
            ->willReturnOnConsecutiveCalls(
                [],             // Pour l'ancien code Insee
                [$newCommune]   // Pour le nouveau code Insee
            );

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($newCommune);

        $reflectionProperty = new \ReflectionProperty($this->command, 'io');
        $reflectionProperty->setValue($this->command, $this->createMock(SymfonyStyle::class));

        $result = $this->command->processCsvRow([null, '44110', 'Nort-sur-Erdros', '44205']);
        $this->assertEquals(['deprecated' => 0, 'renamed' => 1], $result);
    }

    public function testProcessCsvRowDoesNotRenameIfSameName(): void
    {
        $newCommune = $this->createMock(Commune::class);
        $newCommune->method('getNom')->willReturn('Nort-sur-Erdre');
        $newCommune->expects($this->never())->method('setNom');

        $this->communeRepository
            ->expects($this->exactly(2))
            ->method('findBy')
            ->willReturnOnConsecutiveCalls(
                [],             // Pour l'ancien code Insee
                [$newCommune]   // Pour le nouveau code Insee
            );

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $reflectionProperty = new \ReflectionProperty($this->command, 'io');
        $reflectionProperty->setValue($this->command, $this->createMock(SymfonyStyle::class));

        $result = $this->command->processCsvRow([null, '44110', 'Nort-sur-Erdre', '44205']);
        $this->assertEquals(['deprecated' => 0, 'renamed' => 0], $result);
    }

    public function testProcessCsvRowCleansInseeCode(): void
    {
        $this->communeRepository
            ->expects($this->exactly(2))
            ->method('findBy')
            ->willReturnCallback(static function ($criteria) {
                // Vérifie que le code Insee à 4 chiffres est bien complété avec un 0
                if ('01234' === $criteria['codeInsee']) {
                    return [];
                }
                if ('05678' === $criteria['codeInsee']) {
                    return [];
                }

                return [];
            });

        $reflectionProperty = new \ReflectionProperty($this->command, 'io');
        $reflectionProperty->setValue($this->command, $this->createMock(SymfonyStyle::class));

        // Test avec un code Insee à 4 chiffres pour newCommuneInsee et oldCommuneInsee
        $result = $this->command->processCsvRow([null, '1234', 'Test', '5678']);
        $this->assertEquals(['deprecated' => 0, 'renamed' => 0], $result);
    }

    public function testProcessCsvRowCleansCommuneName(): void
    {
        $newCommune = $this->createMock(Commune::class);
        $newCommune->method('getNom')->willReturn('Test');
        $newCommune->expects($this->once())->method('setNom')->with('Test Commune');

        $this->communeRepository
            ->expects($this->exactly(2))
            ->method('findBy')
            ->willReturnOnConsecutiveCalls(
                [],             // Pour l'ancien code Insee
                [$newCommune]   // Pour le nouveau code Insee
            );

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $reflectionProperty = new \ReflectionProperty($this->command, 'io');
        $reflectionProperty->setValue($this->command, $this->createMock(SymfonyStyle::class));

        // Test avec des espaces multiples et des *
        $result = $this->command->processCsvRow([null, '44205', '  Test*  Commune  ', '44999']);
        $this->assertEquals(['deprecated' => 0, 'renamed' => 1], $result);
    }

    public function testProcessCsvRowHandlesMultipleCommunesWithSameInsee(): void
    {
        $oldCommune1 = $this->createMock(Commune::class);
        $oldCommune1->method('getNom')->willReturn('Ancienne 1');
        $oldCommune1->method('getCommuneMergedInto')->willReturn(null);
        $newCommune1 = $this->createMock(Commune::class);
        $newCommune1->method('getNom')->willReturn('Nouveau Nom');
        $oldCommune1->expects($this->once())->method('setCommuneMergedInto')->with($newCommune1);

        $oldCommune2 = $this->createMock(Commune::class);
        $oldCommune2->method('getNom')->willReturn('Ancienne 2');
        $oldCommune2->method('getCommuneMergedInto')->willReturn(null);
        $newCommune2 = $this->createMock(Commune::class);
        $newCommune2->method('getNom')->willReturn('Nouveau Nom');
        $oldCommune2->expects($this->once())->method('setCommuneMergedInto')->with($newCommune2);

        $newCommune1 = $this->createMock(Commune::class);
        $newCommune1->method('getNom')->willReturn('Ancien Nom 1');
        $newCommune1->expects($this->once())->method('setNom')->with('Nouveau Nom');

        $newCommune2 = $this->createMock(Commune::class);
        $newCommune2->method('getNom')->willReturn('Ancien Nom 2');
        $newCommune2->expects($this->once())->method('setNom')->with('Nouveau Nom');

        $this->communeRepository
            ->expects($this->exactly(2))
            ->method('findBy')
            ->willReturnOnConsecutiveCalls(
                [$oldCommune1, $oldCommune2], // Pour l'ancien code Insee
                [$newCommune1, $newCommune2]  // Pour le nouveau code Insee
            );

        $this->communeRepository
            ->method('findOneBy')
            ->willReturnOnConsecutiveCalls(
                $newCommune1,
                $newCommune2,
            );

        $this->entityManager
            ->expects($this->exactly(4))
            ->method('persist');

        $reflectionProperty = new \ReflectionProperty($this->command, 'io');
        $reflectionProperty->setValue($this->command, $this->createMock(SymfonyStyle::class));

        $result = $this->command->processCsvRow([null, '44205', 'Nouveau Nom', '44999']);
        $this->assertEquals(['deprecated' => 2, 'renamed' => 2], $result);
    }
}
