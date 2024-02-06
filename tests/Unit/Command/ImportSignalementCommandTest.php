<?php

namespace App\Tests\Unit\Command;

use App\Command\ImportSignalementCommand;
use App\Entity\Territory;
use App\Repository\TerritoryRepository;
use App\Service\Import\CsvParser;
use App\Service\Import\Signalement\SignalementImportLoader;
use App\Service\UploadHandlerService;
use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ImportSignalementCommandTest extends KernelTestCase
{
    public function testDisplaySuccessfullyMessage()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $territoryRepository = $this->createMock(TerritoryRepository::class);
        $territoryRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['zip' => '01'])
            ->willReturn(
                (new Territory())
                    ->setZip('01')
                    ->setName('Ain')
                    ->setIsActive(true)
            );

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Territory::class)
            ->willReturn($territoryRepository);

        $eventManager = $this->createMock(EventManager::class);
        $entityManager->expects($this->atLeast(1))
            ->method('getEventManager')
            ->willReturn($eventManager);

        $uploadHandlerService = $this->createMock(UploadHandlerService::class);
        $uploadHandlerService->expects($this->once())
            ->method('createTmpFileFromBucket');

        $signalementImportLoader = $this->createMock(SignalementImportLoader::class);
        $signalementImportLoader->expects($this->once())
            ->method('load');

        $signalementImportLoader->expects($this->once())
            ->method('getMetaData')
            ->willReturn([
                'count_signalement' => 1,
                'partners_not_found' => [],
                'motif_cloture_not_found' => [],
                'files_not_found' => [],
            ]);

        $csvParser = $this->createMock(CsvParser::class);
        $csvParser->expects($this->once())
            ->method('parseAsDict')
            ->willReturn([
                'Ref signalement' => '12',
            ]);

        $csvParser->expects($this->once())
            ->method('getHeaders')
            ->willReturn([
                'Ref signalement',
            ]);

        $fileStorage = $this->createMock(FilesystemOperator::class);
        $fileStorage->expects($this->once())
            ->method('fileExists')
            ->willReturn(true);

        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->expects($this->once())
            ->method('get')
            ->with('uploads_tmp_dir')
            ->willReturn('/tmp/');

        $command = $application->add(new ImportSignalementCommand(
            $csvParser,
            $parameterBag,
            $entityManager,
            $fileStorage,
            $uploadHandlerService,
            $signalementImportLoader
        ));

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'territory_zip' => '01',
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('signalement(s) have been imported', $output);
    }

    public function testTerritoryDoesNotExist()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $territoryRepository = $this->createMock(TerritoryRepository::class);
        $territoryRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['zip' => '999'])
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Territory::class)
            ->willReturn($territoryRepository);

        $uploadHandlerService = $this->createMock(UploadHandlerService::class);
        $signalementImportLoader = $this->createMock(SignalementImportLoader::class);
        $csvParser = $this->createMock(CsvParser::class);
        $fileStorage = $this->createMock(FilesystemOperator::class);
        $parameterBag = $this->createMock(ParameterBagInterface::class);

        $command = $application->add(new ImportSignalementCommand(
            $csvParser,
            $parameterBag,
            $entityManager,
            $fileStorage,
            $uploadHandlerService,
            $signalementImportLoader
        ));

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'territory_zip' => '999',
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('Territory does not exists', $output);
    }

    public function testFileDoesNotExist()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $territoryRepository = $this->createMock(TerritoryRepository::class);
        $territoryRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['zip' => '01'])
            ->willReturn(
                (new Territory())
                    ->setZip('01')
                    ->setName('Ain')
                    ->setIsActive(true)
            );

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Territory::class)
            ->willReturn($territoryRepository);

        $eventManager = $this->createMock(EventManager::class);
        $entityManager->expects($this->atLeast(1))
            ->method('getEventManager')
            ->willReturn($eventManager);

        $uploadHandlerService = $this->createMock(UploadHandlerService::class);
        $signalementImportLoader = $this->createMock(SignalementImportLoader::class);
        $csvParser = $this->createMock(CsvParser::class);
        $fileStorage = $this->createMock(FilesystemOperator::class);

        $parameterBag = $this->createMock(ParameterBagInterface::class);
        $parameterBag->expects($this->once())
            ->method('get')
            ->with('uploads_tmp_dir')
            ->willReturn('/tmp/');

        $command = $application->add(new ImportSignalementCommand(
            $csvParser,
            $parameterBag,
            $entityManager,
            $fileStorage,
            $uploadHandlerService,
            $signalementImportLoader
        ));

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'territory_zip' => '01',
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('CSV File does not exists', $output);
    }
}
