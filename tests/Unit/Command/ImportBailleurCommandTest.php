<?php

namespace App\Tests\Unit\Command;

use App\Command\ImportBailleurCommand;
use App\Service\Import\Bailleur\BailleurLoader;
use App\Service\Import\CsvParser;
use App\Service\UploadHandlerService;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ImportBailleurCommandTest extends KernelTestCase
{
    private Stub&CsvParser $csvParser;
    private BailleurLoader $bailleurLoader;
    private UploadHandlerService $uploadHandlerServiceMock;
    private FilesystemOperator $fileStorage;
    private ParameterBagInterface $parameterBag;

    protected function setUp(): void
    {
        $this->csvParser = $this->createStub(CsvParser::class);
        $this->bailleurLoader = $this->createStub(BailleurLoader::class);
        $this->uploadHandlerServiceMock = $this->createStub(UploadHandlerService::class);
        $this->fileStorage = $this->createStub(FilesystemOperator::class);
        $this->parameterBag = static::getContainer()->get(ParameterBagInterface::class);
    }

    public function testDisplayWithFailureMessage(): void
    {
        self::bootKernel();

        $command = new ImportBailleurCommand(
            $this->csvParser,
            $this->bailleurLoader,
            $this->uploadHandlerServiceMock,
            $this->fileStorage,
            $this->parameterBag
        );
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $this->assertStringContainsString('CSV File does not exists', $commandTester->getDisplay());
        $this->assertEquals(1, $commandTester->getStatusCode());
    }

    public function testDisplayWithSucessMessage(): void
    {
        self::bootKernel();

        $fileStorage = $this->createMock(FilesystemOperator::class);
        $fileStorage
            ->expects($this->once())
            ->method('fileExists')
            ->with('csv/bailleurs.csv')
            ->willReturn(true);

        $this->fileStorage = $fileStorage;
        $uploadHandlerServiceMock = $this->createMock(UploadHandlerService::class);
        $uploadHandlerServiceMock
            ->expects($this->once())
            ->method('createTmpFileFromBucket');

        $this->uploadHandlerServiceMock = $uploadHandlerServiceMock;
        $bailleurLoader = $this->createMock(BailleurLoader::class);
        $bailleurLoader
            ->expects($this->once())
            ->method('getMetadata')
            ->willReturn([
                'new_bailleurs' => 10,
                'updated_bailleurs' => 0,
                'deleted_bailleurs' => 0,
                'errors' => ['[125] ligne 2 - Le territoire n\'existe pas'],
            ]);

        $this->bailleurLoader = $bailleurLoader;
        $command = new ImportBailleurCommand(
            $this->csvParser,
            $this->bailleurLoader,
            $this->uploadHandlerServiceMock,
            $this->fileStorage,
            $this->parameterBag
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Le territoire n\'existe pas ', $output);
        $this->assertStringContainsString('10 new bailleur(s) have been imported', $output);
        $commandTester->assertCommandIsSuccessful();
    }
}
