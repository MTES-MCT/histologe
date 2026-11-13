<?php

namespace App\Tests\Unit\Command;

use App\Command\SlugifyDocumentSignalementCommand;
use App\Command\UpdateSignalementDocumentFieldsCommand;
use App\Entity\Address;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Manager\FileManager;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Service\Import\CsvParser;
use App\Service\UploadHandlerService;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UpdateSignalementDocumentFieldsCommandTest extends TestCase
{
    private MockObject&TerritoryRepository $territoryRepository;
    private Stub&SignalementRepository $signalementRepository;
    private Stub&CsvParser $csvParser;
    private Stub&ParameterBagInterface $parameterBag;
    private Stub&FilesystemOperator $fileStorage;
    private Stub&UploadHandlerService $uploadHandlerService;
    private Stub&FileManager $fileManager;
    private Stub&EntityManagerInterface $entityManager;
    private Territory $territory;

    protected function setUp(): void
    {
        $this->territoryRepository = $this->createMock(TerritoryRepository::class);
        $this->signalementRepository = $this->createStub(SignalementRepository::class);
        $this->csvParser = $this->createStub(CsvParser::class);
        $this->parameterBag = $this->createStub(ParameterBagInterface::class);
        $this->fileStorage = $this->createStub(FilesystemOperator::class);
        $this->uploadHandlerService = $this->createStub(UploadHandlerService::class);
        $this->fileManager = $this->createStub(FileManager::class);
        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->territory = (new Territory())->setZip('01')->setName('Ain');
    }

    public function testDisplaySuccessfullyMessage(): void
    {
        $this->territoryRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['zip' => '01'])
            ->willReturn($this->territory);

        $signalementRepositoryMock = $this->createMock(SignalementRepository::class);
        $address = (new Address())->setTerritory($this->territory);
        $expectedCalls = [
            [$this->territory, '1000', (new Signalement())->setAddress($address)->setReference('2023-1000')],
            [$this->territory, '1000', (new Signalement())->setAddress($address)->setReference('2023-1000')],
            [$this->territory, '1001', (new Signalement())->setAddress($address)->setReference('2023-1001')],
            [$this->territory, '1001', (new Signalement())->setAddress($address)->setReference('2023-1001')],
            [$this->territory, '1001', (new Signalement())->setAddress($address)->setReference('2023-1001')],
        ];
        $callIndex = 0;
        $signalementRepositoryMock
            ->expects($this->atLeast(1))
            ->method('findByReferenceChunk')
            ->willReturnCallback(function ($territory, $reference) use ($expectedCalls, &$callIndex) {
                $this->assertSame($expectedCalls[$callIndex][0], $territory);
                $this->assertSame($expectedCalls[$callIndex][1], $reference);

                return $expectedCalls[$callIndex++][2];
            });

        $fromFile = 'csv/'.SlugifyDocumentSignalementCommand::PREFIX_FILENAME_STORAGE_MAPPING_SLUGGED.'01.csv';
        $toFile = $this->parameterBag->get('uploads_tmp_dir').'mapping_doc_signalement_01.csv';

        $fileStorage = $this->createMock(FilesystemOperator::class);
        $fileStorage->expects($this->once())
            ->method('fileExists')
            ->with($fromFile)
            ->willReturn(true);

        $uploadHandlerService = $this->createMock(UploadHandlerService::class);
        $uploadHandlerService->expects($this->once())
            ->method('createTmpFileFromBucket')
            ->with($fromFile, $toFile);

        $csvParser = $this->createMock(CsvParser::class);
        $csvParser->expects($this->once())
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
            $this->territoryRepository,
            $signalementRepositoryMock,
            $csvParser,
            $this->parameterBag,
            $fileStorage,
            $uploadHandlerService,
            $this->fileManager,
            $this->entityManager,
        );

        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'zip' => '01',
        ]);

        $this->assertStringContainsString('5 files signalement(s) updated', $commandTester->getDisplay());
    }

    public function testDisplayFailedMessageTerritoryDoesNotExist(): void
    {
        $this->territoryRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['zip' => '99'])
            ->willReturn(null);

        $command = new UpdateSignalementDocumentFieldsCommand(
            $this->territoryRepository,
            $this->signalementRepository,
            $this->csvParser,
            $this->parameterBag,
            $this->fileStorage,
            $this->uploadHandlerService,
            $this->fileManager,
            $this->entityManager,
        );
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'zip' => '99',
        ]);

        $this->assertStringContainsString('Territory does not exist', $commandTester->getDisplay());
    }

    public function testDisplayFailedMessageWithMappingFileDoesNotExist(): void
    {
        $this->territoryRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['zip' => '99'])
            ->willReturn($this->territory);

        $fromFile = 'csv/'.SlugifyDocumentSignalementCommand::PREFIX_FILENAME_STORAGE_MAPPING_SLUGGED.'99.csv';

        $fileStorage = $this->createMock(FilesystemOperator::class);
        $fileStorage->expects($this->once())
            ->method('fileExists')
            ->with($fromFile)
            ->willReturn(false);

        $command = new UpdateSignalementDocumentFieldsCommand(
            $this->territoryRepository,
            $this->signalementRepository,
            $this->csvParser,
            $this->parameterBag,
            $fileStorage,
            $this->uploadHandlerService,
            $this->fileManager,
            $this->entityManager,
        );
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'zip' => '99',
        ]);

        $this->assertStringContainsString(
            'CSV Mapping file csv/mapping_doc_signalement_slugged_99.csv does not',
            $commandTester->getDisplay()
        );
    }
}
