<?php

namespace App\Tests\Unit\Command;

use App\Command\ImportGridAffectationCommand;
use App\Manager\TerritoryManager;
use App\Service\Import\CsvParser;
use App\Service\Import\GridAffectation\GridAffectationLoader;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\UploadHandlerService;
use App\Tests\FixturesHelper;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ImportGridAffectationCommandTest extends KernelTestCase
{
    use FixturesHelper;

    private MockObject|FilesystemOperator $fileStorage;
    private MockObject|ParameterBagInterface $parameterBag;
    private MockObject|TerritoryManager $territoryManager;
    private MockObject|CsvParser $csvParser;
    private MockObject|GridAffectationLoader $gridAffectationLoader;
    private MockObject|UploadHandlerService $uploadHandlerServiceMock;
    private MockObject|NotificationMailerRegistry $notificationMailerRegistryMock;
    private MockObject|UrlGeneratorInterface $urlGeneratorMock;

    protected function setUp(): void
    {
        $this->fileStorage = $this->createMock(FilesystemOperator::class);
        $this->parameterBag = static::getContainer()->get(ParameterBagInterface::class);
        $this->territoryManager = $this->createMock(TerritoryManager::class);
        $this->csvParser = $this->createMock(CsvParser::class);
        $this->gridAffectationLoader = $this->createMock(GridAffectationLoader::class);
        $this->uploadHandlerServiceMock = $this->createMock(UploadHandlerService::class);
        $this->notificationMailerRegistryMock = $this->createMock(NotificationMailerRegistry::class);
        $this->urlGeneratorMock = $this->createMock(UrlGeneratorInterface::class);
        $this->urlGeneratorMock
            ->expects($this->once())
            ->method('generate')
            ->willReturn('https://dummy-url.local/bo/partenaires');
    }

    public function testDisplaySuccessfullyMessageWithPartnersAndUserCreated(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $this->fileStorage
            ->expects($this->once())
            ->method('fileExists')
            ->with('csv/grille_affectation_01.csv')
            ->willReturn(true);

        $this->territoryManager
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($this->getTerritory(isActive: 0));

        $this->gridAffectationLoader
            ->expects($this->once())
            ->method('getMetaData')
            ->willReturn(['nb_partners' => 10, 'nb_users_created' => 55, 'errors' => []]);

        $this->uploadHandlerServiceMock
            ->expects($this->once())
            ->method('createTmpFileFromBucket');

        $this->notificationMailerRegistryMock
            ->expects($this->once())
            ->method('send')
            ->willReturn(true);

        $command = $application->add(new ImportGridAffectationCommand(
            $this->fileStorage,
            $this->parameterBag,
            $this->csvParser,
            $this->territoryManager,
            $this->gridAffectationLoader,
            $this->uploadHandlerServiceMock,
            $this->notificationMailerRegistryMock,
            $this->urlGeneratorMock,
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

    public function testDisplayFailedMessage(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);

        $this->fileStorage
            ->expects($this->once())
            ->method('fileExists')
            ->with('csv/grille_affectation_13-1.csv')
            ->willReturn(true);

        $this->territoryManager
            ->expects($this->once())
            ->method('findOneBy')
            ->willReturn($this->getTerritory(name: 'Bouches-du-Rhône', zip: 13, isActive: 1));

        $this->gridAffectationLoader
            ->expects($this->once())
            ->method('getMetaData')
            ->willReturn(['nb_partners' => 1, 'nb_users_created' => 1, 'errors' => []]);

        $command = $application->add(new ImportGridAffectationCommand(
            $this->fileStorage,
            $this->parameterBag,
            $this->csvParser,
            $this->territoryManager,
            $this->gridAffectationLoader,
            $this->uploadHandlerServiceMock,
            $this->notificationMailerRegistryMock,
            $this->urlGeneratorMock,
        ));

        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'territory_zip' => '13',
            '--file-version' => 1,
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('1 partner(s)', $output);
        $this->assertStringContainsString('1 user(s)', $output);
        $this->assertStringContainsString('Bouches-du-Rhône has been update', $output);
    }
}
