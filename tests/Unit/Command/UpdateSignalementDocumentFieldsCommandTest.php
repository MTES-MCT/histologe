<?php

namespace App\Tests\Unit\Command;

use App\Command\SlugifyDocumentSignalementCommand;
use App\Command\UpdateSignalementDocumentFieldsCommand;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Manager\SignalementManager;
use App\Manager\TerritoryManager;
use App\Repository\SignalementRepository;
use App\Service\Import\CsvParser;
use App\Service\UploadHandlerService;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UpdateSignalementDocumentFieldsCommandTest extends TestCase
{
    private TerritoryManager|MockObject $territoryManager;
    private SignalementManager|MockObject $signalementManager;
    private CsvParser|MockObject $csvParser;
    private ParameterBagInterface|MockObject $parameterBag;
    private FilesystemOperator|MockObject $fileStorage;
    private UploadHandlerService|MockObject $uploadHandlerService;
    private LoggerInterface|MockObject $logger;
    private Territory $territory;

    protected function setUp(): void
    {
        $this->territoryManager = $this->createMock(TerritoryManager::class);
        $this->signalementManager = $this->createMock(SignalementManager::class);
        $this->csvParser = $this->createMock(CsvParser::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->fileStorage = $this->createMock(FilesystemOperator::class);
        $this->uploadHandlerService = $this->createMock(UploadHandlerService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->territory = (new Territory())->setZip('01')->setName('Ain');
    }

    public function testDisplaySuccessfullyMessage(): void
    {
        $this->territoryManager
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['zip' => '01'])
            ->willReturn($this->territory);

        $signalementRepositoryMock = $this->createMock(SignalementRepository::class);
        $signalementRepositoryMock
            ->expects($this->atLeast(1))
            ->method('findByReferenceChunk')
            ->withConsecutive(
                [$this->territory, '1000'],
                [$this->territory, '1001'],
            )
            ->willReturn(
                (new Signalement())->setTerritory($this->territory)->setReference('2023-1000'),
                (new Signalement())->setTerritory($this->territory)->setReference('2023-1001'),
            );

        $this->signalementManager
            ->expects($this->atLeast(1))
            ->method('getRepository')
            ->willReturn($signalementRepositoryMock);

        $fromFile = 'csv/'.SlugifyDocumentSignalementCommand::PREFIX_FILENAME_STORAGE.'_01.csv';
        $toFile = $this->parameterBag->get('uploads_tmp_dir').'mapping_doc_signalement_01.csv';

        $this->fileStorage->expects($this->once())
            ->method('fileExists')
            ->with($fromFile)
            ->willReturn(true);

        $this->uploadHandlerService->expects($this->once())
            ->method('createTmpFileFromBucket')
            ->with($fromFile, $toFile);

        $this->csvParser->expects($this->once())
            ->method('parseAsDict')
            ->with($toFile)
            ->willReturn([
                [
                    'id_EnregistrementAttachment' => 1,
                    'id_Enregistrement' => 1000,
                    'sAttachFileName' => 'file-random-01.jpg',
                ],
                [
                    'id_EnregistrementAttachment' => 2,
                    'id_Enregistrement' => 1000,
                    'sAttachFileName' => 'file-random-02.pdf',
                ],
                [
                    'id_EnregistrementAttachment' => 3,
                    'id_Enregistrement' => 1001,
                    'sAttachFileName' => 'file-random-01.pdf',
                ],
                [
                    'id_EnregistrementAttachment' => 4,
                    'id_Enregistrement' => 1001,
                    'sAttachFileName' => 'file-random-02.pdf',
                ],
                [
                    'id_EnregistrementAttachment' => 5,
                    'id_Enregistrement' => 1001,
                    'sAttachFileName' => 'file-random-03.png',
                ],
            ]);

        $command = new UpdateSignalementDocumentFieldsCommand(
            $this->territoryManager,
            $this->signalementManager,
            $this->csvParser,
            $this->parameterBag,
            $this->fileStorage,
            $this->uploadHandlerService,
            $this->logger
        );

        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'zip' => '01',
        ]);

        $this->assertStringContainsString('2 Signalement(s) updated', $commandTester->getDisplay());
    }

    public function testDisplayFailedMessageTerritoryDoesNotExist(): void
    {
        $this->territoryManager
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['zip' => '99'])
            ->willReturn(null);

        $command = new UpdateSignalementDocumentFieldsCommand(
            $this->territoryManager,
            $this->signalementManager,
            $this->csvParser,
            $this->parameterBag,
            $this->fileStorage,
            $this->uploadHandlerService,
            $this->logger
        );
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'zip' => '99',
        ]);

        $this->assertStringContainsString('Territory does not exist', $commandTester->getDisplay());
    }

    public function testDisplayFailedMessageMappingFileDoesNotExist(): void
    {
        $this->territoryManager
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['zip' => '99'])
            ->willReturn($this->territory);

        $fromFile = 'csv/'.SlugifyDocumentSignalementCommand::PREFIX_FILENAME_STORAGE.'_99.csv';

        $this->fileStorage->expects($this->once())
            ->method('fileExists')
            ->with($fromFile)
            ->willReturn(false);

        $command = new UpdateSignalementDocumentFieldsCommand(
            $this->territoryManager,
            $this->signalementManager,
            $this->csvParser,
            $this->parameterBag,
            $this->fileStorage,
            $this->uploadHandlerService,
            $this->logger
        );
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'zip' => '99',
        ]);

        $this->assertStringContainsString(
            'CSV Mapping file does not exist',
            $commandTester->getDisplay()
        );
    }
}
