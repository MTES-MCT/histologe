<?php

namespace App\Tests\Unit\Service;

use App\Repository\FileRepository;
use App\Repository\SignalementQualificationRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Service\FileListService;
use App\Service\Signalement\Qualification\QualificationStatusService;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\SecurityBundle\Security;

class FileListServiceTest extends WebTestCase
{
    private KernelBrowser $client;
    private FileRepository $fileRepository;
    private SignalementQualificationRepository $signalementQualificationRepository;
    private Security $security;
    private QualificationStatusService $qualificationStatusService;
    private SignalementRepository $signalementRepository;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->fileRepository = self::getContainer()->get(FileRepository::class);
        $this->signalementQualificationRepository = self::getContainer()->get(SignalementQualificationRepository::class);
        $this->security = self::getContainer()->get(Security::class);
        $this->qualificationStatusService = self::getContainer()->get(QualificationStatusService::class);
        $this->signalementRepository = self::getContainer()->get(SignalementRepository::class);
        $this->userRepository = self::getContainer()->get(UserRepository::class);

        $user = $this->userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->client->loginUser($user);
    }

    public function testGetFileChoicesForSignalement(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2022-000000000001']);

        $choices = (new FileListService(
            $this->fileRepository,
            $this->signalementQualificationRepository,
            $this->security,
            $this->qualificationStatusService,
            true,
        ))->getFileChoicesForSignalement($signalement);

        $this->assertArrayHasKey('Documents de la situation', $choices);
        $this->assertArrayNotHasKey('Documents liés à la procédure', $choices);
        $this->assertArrayHasKey('Documents types', $choices);
        $this->assertCount(3, $choices['Documents de la situation']);
    }

    public function testGetFileChoicesForSignalementNDE(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000008']);

        $choices = (new FileListService(
            $this->fileRepository,
            $this->signalementQualificationRepository,
            $this->security,
            $this->qualificationStatusService,
            true,
        ))->getFileChoicesForSignalement($signalement);

        $this->assertArrayHasKey('Documents de la situation', $choices);
        $this->assertArrayNotHasKey('Documents liés à la procédure', $choices);
        $this->assertArrayHasKey('Documents types', $choices);
        $this->assertArrayHasKey('Autre', $choices['Documents types']);
        $this->assertCount(3, $choices['Documents de la situation']);
        $this->assertCount(6, $choices['Documents types']['Autre']);
    }
}
