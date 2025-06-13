<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Manager\UserManager;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Security\User\SignalementUser;
use App\Service\Signalement\SignalementFileProcessor;
use App\Service\UploadHandlerService;
use App\Tests\SessionHelper;
use App\Tests\UserHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\RouterInterface;

class SignalementFileControllerTest extends WebTestCase
{
    use SessionHelper;
    use UserHelper;
    private ?KernelBrowser $client = null;
    private ?Signalement $signalement = null;
    private ?SignalementUser $signalementUser = null;
    private RouterInterface $router;
    private SignalementRepository $signalementRepository;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        /* @var Signalement $signalement */
        $this->signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2022-000000000001']);
        $this->signalementUser = $this->getSignalementUser($this->signalement);
        /* @var RouterInterface $router */
        $this->router = self::getContainer()->get(RouterInterface::class);
    }

    public function testAddSuccessFileSignalement()
    {
        $imageFile = new UploadedFile(
            __DIR__.'/../../files/sample.jpg',
            'sample.jpg',
            'image/jpeg',
            null,
            true
        );

        $documentFile = new UploadedFile(
            __DIR__.'/../../files/sample.pdf',
            'sample.pdf',
            'application/pdf',
            null,
            true
        );

        $this->client->loginUser($this->signalementUser, 'code_suivi');

        $signalementFileProcessor = $this->createMock(SignalementFileProcessor::class);
        $signalementFileProcessor
            ->method('process')
            ->willReturn([
                [
                    'file' => 'sample1234.jpg',
                    'title' => 'sample.jpg',
                    'date' => new \DateTimeImmutable(),
                    'type' => 'photo',
                    'documentType' => DocumentType::AUTRE, ],
                [
                    'file' => 'sample1234.pdf',
                    'title' => 'sample.pdf',
                    'date' => new \DateTimeImmutable(),
                    'type' => 'document',
                    'documentType' => DocumentType::AUTRE, ],
            ]);
        $signalementFileProcessor
            ->method('isValid')
            ->willReturn(true);

        self::getContainer()->set(SignalementFileProcessor::class, $signalementFileProcessor);

        $route = $this->router->generate('signalement_add_file', ['code' => $this->signalement->getCodeSuivi()]);
        $this->client->request('POST', $route,
            [
                '_token' => $this->generateCsrfToken($this->client, 'signalement_add_file_'.$this->signalement->getId()),
            ],
            [
                'signalement-add-file' => [
                    'photos' => [$imageFile],
                    'documents' => [$documentFile],
                ],
            ],
            [
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            ]
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAddFailureFileSignalement()
    {
        $imageFile = new UploadedFile(
            __DIR__.'/../../files/sample.heic',
            'sample.heic',
            'image/heif',
            null,
            true
        );

        $this->client->loginUser($this->signalementUser, 'code_suivi');

        $route = $this->router->generate('signalement_add_file', ['code' => $this->signalement->getCodeSuivi()]);
        $this->client->request(
            'POST',
            $route,
            [
                '_token' => $this->generateCsrfToken($this->client, 'signalement_add_file_'.$this->signalement->getId()),
                'email' => $this->signalement->getMailOccupant() ?? $this->signalement->getMailDeclarant(),
            ],
            [
                'signalement-add-file' => [
                    'photos' => [$imageFile],
                ],
            ],
            [
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            ]
        );
        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString('Le fichier a une extension heic mais est au format', $this->client->getResponse()->getContent());
    }

    public function testDeleteFileAccessDeniedSignalement()
    {
        $this->client->catchExceptions(false);
        $route = $this->router->generate('signalement_delete_file', ['code' => $this->signalement->getCodeSuivi()]);
        /** @var File $file */
        $file = $this->signalement->getFiles()->first();
        try {
            $this->client->request('POST', $route, [
                '_token' => $this->generateCsrfToken($this->client, 'signalement_delete_file_'.$this->signalement->getId()),
                'file_id' => $file->getId(),
            ]);

            $this->fail('L\'exception AccessDeniedException n\'a pas été levée.');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testDeleteFileSuccessSignalement()
    {
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000027']);

        $route = $this->router->generate('signalement_delete_file', ['code' => $signalement->getCodeSuivi()]);
        /** @var File $file */
        $file = $signalement->getFiles()->last();

        $uploadHandlerServiceMock = $this->createMock(UploadHandlerService::class);
        $uploadHandlerServiceMock
            ->method('deleteFile')
            ->willReturn(true);

        self::getContainer()->set(UploadHandlerService::class, $uploadHandlerServiceMock);

        $signalementUser = new SignalementUser(
            $signalement->getCodeSuivi().':'.UserManager::OCCUPANT,
            $signalement->getMailOccupant(),
            $signalement->getSignalementUsager()->getOccupant()
        );
        $this->client->loginUser($signalementUser, 'code_suivi');

        $this->client->request('POST', $route, [
            '_token' => $this->generateCsrfToken($this->client, 'signalement_delete_file_'.$signalement->getId()),
            'file_id' => $file->getId(),
        ]);

        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000027']);
        /** @var Suivi $lastSuivi */
        $lastSuivi = $signalement->getSuivis()->last();
        $this->assertStringContainsString('Photo supprimée', $lastSuivi->getDescription());
        $this->assertStringContainsString($file->getFilename(), $lastSuivi->getDescription());

        $redirectUrl = $this->client->getResponse()->headers->get('Location');
        $this->client->request('GET', $redirectUrl);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testGeneratePdfSignalement()
    {
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2024-000000000012']);

        $route = $this->router->generate('signalement_gen_pdf', ['code' => $signalement->getCodeSuivi()]);

        // on logue l'occupant du signalement
        /** @var User $usager */
        $usager = $this->userRepository->findOneBy(['email' => $signalement->getMailOccupant()]);
        $signalementUser = new SignalementUser(
            userIdentifier: $signalement->getCodeSuivi().':'.UserManager::OCCUPANT,
            email: $signalement->getMailOccupant(),
            user: $usager
        );
        $this->client->loginUser($signalementUser, 'code_suivi');

        $this->client->request(
            'GET',
            $route,
        );

        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());

        $redirectUrl = $this->client->getResponse()->headers->get('Location');
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', $redirectUrl);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString(
            'Le signalement au format PDF vous sera envoyé par e-mail à',
            $crawler->filter('.fr-alert')->text()
        );
    }
}
