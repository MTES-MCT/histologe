<?php

namespace App\Tests\Unit\Command;

use App\Command\ImportGridAffectationCommand;
use App\Entity\Territory;
use App\Manager\TerritoryManager;
use App\Service\Import\CsvParser;
use App\Service\Import\GridAffectation\GridAffectationLoader;
use App\Service\Mailer\NotificationService;
use App\Service\UploadHandlerService;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ImportGridAffectationCommandTest extends KernelTestCase
{
    public function testDisplaySuccessfullyMessageWithPartnersAndUserCreated()
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $fileStorage = $this->createMock(FilesystemOperator::class);
        $fileStorage
            ->expects($this->once())
            ->method('fileExists')
            ->with('csv/grille_affectation_01.csv')
            ->willReturn(true);

        $parameterBag = static::getContainer()->get(ParameterBagInterface::class);
        $csvParser = $this->createMock(CsvParser::class);

        $territoryManager = $this->createMock(TerritoryManager::class);
        $territoryManager
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($this->getTerritory());

        $gridAffectationLoader = $this->createMock(GridAffectationLoader::class);
        $gridAffectationLoader
            ->expects($this->once())
            ->method('getMetaData')
            ->willReturn(['nb_partners' => 10, 'nb_users' => 55]);

        $uploadHandlerServiceMock = $this->createMock(UploadHandlerService::class);
        $uploadHandlerServiceMock
            ->expects($this->once())
            ->method('createTmpFileFromBucket');

        $notificationServiceMock = $this->createMock(NotificationService::class);
        $notificationServiceMock
            ->expects($this->once())
            ->method('send')
            ->willReturn(true);

        $command = $application->add(new ImportGridAffectationCommand(
            $fileStorage,
            $parameterBag,
            $csvParser,
            $territoryManager,
            $gridAffectationLoader,
            $uploadHandlerServiceMock,
            $notificationServiceMock,
        ));

        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'territory_zip' => '01',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('10 partner(s)', $output);
        $this->assertStringContainsString('55 user(s)', $output);
        $this->assertStringContainsString('Ain has been activated', $output);
    }

    private function getTerritory()
    {
        return (new Territory())
            ->setName('Ain')
            ->setZip('01')
            ->setBbox([])
            ->setIsActive(false);
    }
}
