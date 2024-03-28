<?php

namespace App\Tests\Unit\Command;

use App\Command\ImportBailleurCommand;
use App\Service\Import\Bailleur\BailleurLoader;
use App\Service\Import\CsvParser;
use App\Service\UploadHandlerService;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ImportBailleurCommandTest extends KernelTestCase
{
    private MockObject|CsvParser $csvParser;
    private MockObject|BailleurLoader $bailleurLoader;
    private MockObject|UploadHandlerService $uploadHandlerServiceMock;
    private MockObject|FilesystemOperator $fileStorage;
    private MockObject|ParameterBagInterface $parameterBag;

    protected function setUp(): void
    {
        $this->csvParser = $this->createMock(CsvParser::class);
        $this->bailleurLoader = $this->createMock(BailleurLoader::class);
        $this->uploadHandlerServiceMock = $this->createMock(UploadHandlerService::class);
        $this->fileStorage = $this->createMock(FilesystemOperator::class);
        $this->parameterBag = static::getContainer()->get(ParameterBagInterface::class);
    }

    public function testDisplayWithFailureMessage(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $command = $application->add(new ImportBailleurCommand(
            $this->csvParser,
            $this->bailleurLoader,
            $this->uploadHandlerServiceMock,
            $this->fileStorage,
            $this->parameterBag
        ));
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);
        $this->assertStringContainsString('CSV File does not exists', $commandTester->getDisplay());
        $this->assertEquals(1, $commandTester->getStatusCode());
    }

    public function testDisplayWithSucessMessage(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $this->fileStorage
            ->expects($this->once())
            ->method('fileExists')
            ->with('csv/bailleurs.csv')
            ->willReturn(true);

        $this->uploadHandlerServiceMock
            ->expects($this->once())
            ->method('createTmpFileFromBucket');

        $this->bailleurLoader
            ->expects($this->once())
            ->method('getMetadata')
            ->willReturn([
                'count_bailleurs' => 10,
                'errors' => ['[Wallis-et-Futuna] ligne 2 - Le territoire n\'existe pas'],
            ]);

        $command = $application->add(new ImportBailleurCommand(
            $this->csvParser,
            $this->bailleurLoader,
            $this->uploadHandlerServiceMock,
            $this->fileStorage,
            $this->parameterBag
        ));

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Le territoire n\'existe pas ', $output);
        $this->assertStringContainsString('10 bailleur(s) have been imported', $output);
        $commandTester->assertCommandIsSuccessful();
    }
}
