<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Enum\DocumentType;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Repository\SignalementRepository;
use App\Security\User\SignalementUser;
use App\Service\Signalement\SignalementFileProcessor;
use App\Service\UploadHandlerService;
use App\Tests\SessionHelper;
use App\Tests\UserHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
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

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $this->signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2022-000000000001']);
        $this->signalementUser = $this->getSignalementUser($this->signalement);
        $this->router = self::getContainer()->get(RouterInterface::class);
    }

    public function testAddSuccessFileSignalement(): void
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

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testDeleteFileAccessDeniedSignalement(): void
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

    public function testDeleteFileSuccessSignalement(): void
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

        $signalementUser = $this->getSignalementUser($signalement);
        $this->client->loginUser($signalementUser, 'code_suivi');

        $this->client->request('POST', $route, [
            '_token' => $this->generateCsrfToken($this->client, 'signalement_delete_file_'.$signalement->getId()),
            'file_id' => $file->getId(),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000027']);
        /** @var Suivi $lastSuivi */
        $lastSuivi = $signalement->getSuivis()->last();
        $this->assertStringContainsString('Photo supprimée', $lastSuivi->getDescription());
        $this->assertStringContainsString($file->getFilename(), $lastSuivi->getDescription());

        $redirectUrl = $this->client->getResponse()->headers->get('Location');
        $this->client->request('GET', $redirectUrl);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testGeneratePdfSignalement(): void
    {
        /** @var Signalement $signalement */
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2024-000000000012']);

        $route = $this->router->generate('signalement_gen_pdf', ['code' => $signalement->getCodeSuivi()]);

        $signalementUser = $this->getSignalementUser($signalement);
        $this->client->loginUser($signalementUser, 'code_suivi');

        $this->client->request(
            'GET',
            $route,
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FOUND);

        $redirectUrl = $this->client->getResponse()->headers->get('Location');
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', $redirectUrl);
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $msg = 'Le dossier au format PDF a bien été envoyé par e-mail à l\'adresse suivante : nelson.monfort@yopmail.com. L\'envoi peut prendre plusieurs minutes. N\'oubliez pas de consulter vos courriers indésirables (spam) !';
        $this->assertStringContainsString($msg, $crawler->filter('.fr-notice .fr-notice__desc')->text());
    }
}
