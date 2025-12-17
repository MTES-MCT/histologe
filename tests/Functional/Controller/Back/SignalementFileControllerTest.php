<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\Signalement;
use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Service\Signalement\SignalementFileProcessor;
use App\Tests\SessionHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class SignalementFileControllerTest extends WebTestCase
{
    use SessionHelper;
    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private SignalementRepository $signalementRepository;
    private RouterInterface $router;
    private ?User $user = null;
    private ?Signalement $signalement = null;
    private SignalementFileProcessor&MockObject $signalementFileProcessorMock;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->router = self::getContainer()->get(RouterInterface::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $this->signalementFileProcessorMock = $this->createMock(SignalementFileProcessor::class);

        $this->user = $this->userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->client->loginUser($this->user);
        $this->signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2022-000000000001']);
    }

    public function testAddSuccessFileSignalement(): void
    {
        $imageFile = new UploadedFile(
            __DIR__.'/../../../files/sample.jpg',
            'sample.jpg',
            'image/jpeg',
            null,
            true
        );

        $documentFile = new UploadedFile(
            __DIR__.'/../../../files/sample.pdf',
            'sample.pdf',
            'application/pdf',
            null,
            true
        );

        $this->client->loginUser($this->user);

        $this->signalementFileProcessorMock->method('isValid')->willReturn(true);
        self::getContainer()->set(SignalementFileProcessor::class, $this->signalementFileProcessorMock);

        $route = $this->router->generate('back_signalement_add_file', ['uuid' => $this->signalement->getUuid()]);
        $this->client->request('POST', $route,
            ['_token' => $this->generateCsrfToken($this->client, 'signalement_add_file_'.$this->signalement->getId())],
            ['signalement-add-file' => [$imageFile, $documentFile]]
        );

        $this->assertResponseIsSuccessful();
    }

    public function testGeneratePdfSignalement(): void
    {
        $this->client->loginUser($this->user);

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2022-000000000001']);

        $route = $this->router->generate('back_signalement_gen_pdf', ['uuid' => $signalement->getUuid()]);
        $this->client->request('GET', $route);

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stayOnPage', $response);
        $this->assertArrayHasKey('flashMessages', $response);
        $this->assertTrue($response['stayOnPage']);
        $msgFlash = 'L\'export PDF a bien été envoyé par e-mail à l\'adresse suivante : admin-01@signal-logement.fr. N\'oubliez pas de consulter vos courriers indésirables (spam) !';
        $this->assertEquals($msgFlash, $response['flashMessages'][0]['message']);
    }

    public function testAddFileSignalementNotDeny(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-13-05@signal-logement.fr']);
        $this->client->loginUser($user);

        $route = $this->router->generate('back_signalement_add_file', ['uuid' => '00000000-0000-0000-2023-000000000009']);
        $this->client->request('POST', $route);

        $this->assertResponseStatusCodeSame(400);
        $this->assertStringContainsString(
            'Token CSRF invalide ou param\u00e8tre manquant, veuillez recharger la page',
            (string) $this->client->getResponse()->getContent()
        );
    }

    public function testAddFileSignalementDeny(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-13-05@signal-logement.fr']);
        $this->client->loginUser($user);

        $route = $this->router->generate('back_signalement_add_file', ['uuid' => '00000000-0000-0000-2023-000000000012']);
        $this->client->request('POST', $route);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testEditFileSignalementSuccess(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000009']);
        $route = $this->router->generate('back_signalement_edit_file', ['uuid' => $signalement->getUuid()]);
        $this->client->request(
            'POST',
            $route,
            [
                'file_id' => $signalement->getFiles()[0]->getId(),
                'documentType' => 'AUTRE',
                'description' => 'Comme on peux le voir la situation est critique, il faut agir rapidement.',
                'from' => 'edit',
                '_token' => $this->generateCsrfToken($this->client, 'signalement_edit_file_'.$signalement->getId()),
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertEquals('{"response":"success"}', (string) $this->client->getResponse()->getContent());
        $flashBag = $this->client->getRequest()->getSession()->getFlashBag(); // @phpstan-ignore-line
        $this->assertTrue($flashBag->has('success'));
        $successMessages = $flashBag->get('success');
        $this->assertEquals(['title' => 'Document modifié', 'message' => 'Le document a bien été modifié.'], $successMessages[0]);
    }

    public function testEditFileSignalementError(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000009']);
        $route = $this->router->generate('back_signalement_edit_file', ['uuid' => $signalement->getUuid()]);
        $file = $signalement->getFiles()->filter(function ($file) {
            return $file->isTypeImage();
        })->current();
        if (!$file) {
            $this->fail('No file found for the signalement');
        }

        $message = 'Je vais écrire un roman, lorem ipsum dolor sit amet, consectetur adipiscing elit.
        Nulla nec purus feugiat, ultricies nunc nec, tincidunt nunc. Nulla facilisi.
        Nullam nec... Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla nec purus feugiat, ultricies nunc nec, tincidunt nunc.
        Nulla facilisi. Nullam nec...';

        $this->client->request(
            'POST',
            $route,
            [
                'file_id' => $file->getId(),
                'documentType' => 'AUTRE',
                'description' => $message,
                '_token' => $this->generateCsrfToken($this->client, 'signalement_edit_file_'.$signalement->getId()),
            ]
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertStringContainsString(
            'La description ne doit pas d\u00e9passer 255 caract\u00e8res',
            (string) $this->client->getResponse()->getContent()
        );
    }
}
