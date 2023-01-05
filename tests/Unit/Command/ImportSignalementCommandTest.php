<?php

namespace App\Tests\Unit\Command;

use App\Command\ImportSignalementCommand;
use App\Entity\Territory;
use App\EventListener\ActivityListener;
use App\Repository\TerritoryRepository;
use App\Service\Parser\CsvParser;
use App\Service\Signalement\Import\SignalementImportLoader;
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
        $eventManager->expects($this->once())
            ->method('removeEventSubscriber');

        $eventManager->expects($this->once())
            ->method('addEventSubscriber');

        $entityManager->expects($this->atLeast(2))
            ->method('getEventManager')
            ->willReturn($eventManager);

        $activityListener = $this->createMock(ActivityListener::class);

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
            $activityListener,
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
}
