<?php

namespace App\Tests\Unit\Command;

use App\Command\ImportDesordresTablesCommand;
use App\Service\Import\CsvParser;
use App\Service\Import\Desordres\DesordresTablesLoader;
use App\Service\UploadHandlerService;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ImportDesordresTablesCommandTest extends KernelTestCase
{
    public function testDisplaySuccessfullyMessage()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $fileStorage = $this->createMock(FilesystemOperator::class);
        $fileStorage
            ->expects($this->once())
            ->method('fileExists')
            ->with('csv/desordres_tables.csv')
            ->willReturn(true);

        $parameterBag = static::getContainer()->get(ParameterBagInterface::class);
        $csvParser = $this->createMock(CsvParser::class);

        $desordresTablesLoader = $this->createMock(DesordresTablesLoader::class);
        $desordresTablesLoader
            ->expects($this->once())
            ->method('getMetaData')
            ->willReturn(
                ['count_desordre_categorie_created' => 13,
                'count_desordre_critere_created' => 63,
                'count_desordre_precision_created' => 137,
                'count_desordre_precision_updated' => 27,
            ]
            );

        $uploadHandlerServiceMock = $this->createMock(UploadHandlerService::class);
        $uploadHandlerServiceMock
            ->expects($this->once())
            ->method('createTmpFileFromBucket');

        $command = $application->add(new ImportDesordresTablesCommand(
            $csvParser,
            $parameterBag,
            $fileStorage,
            $uploadHandlerServiceMock,
            $desordresTablesLoader,
        ));

        $commandTester = new CommandTester($command);

        $commandTester->execute([
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('13 desordre_categorie have been created', $output);
    }
}
