<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\Signalement;
use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->router = self::getContainer()->get(RouterInterface::class);
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);

        $this->user = $this->userRepository->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $this->client->loginUser($this->user);
        /* @var Signalement $signalement */
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

        $route = $this->router->generate('back_signalement_add_file', ['uuid' => $this->signalement->getUuid()]);
        $this->client->request('POST', $route, [
            '_token' => $this->generateCsrfToken($this->client, 'signalement_add_file_'.$this->signalement->getId()),
        ], [
            'signalement-add-file' => [
                'photos' => [$imageFile],
                'documents' => [$documentFile],
            ],
        ]);

        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());

        $redirectUrl = $this->client->getResponse()->headers->get('Location');
        $this->client->request('GET', $redirectUrl);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    public function testAddFailureFileSignalement(): void
    {
        $imageFile = new UploadedFile(
            __DIR__.'/../../../files/sample.heic',
            'sample.heic',
            'image/heif',
            null,
            true
        );

        $this->client->loginUser($this->user);

        $route = $this->router->generate('back_signalement_add_file', ['uuid' => $this->signalement->getUuid()]);
        $this->client->request('POST', $route, [
            '_token' => $this->generateCsrfToken($this->client, 'signalement_add_file_'.$this->signalement->getId()),
        ], [
            'signalement-add-file' => [
                'photos' => [$imageFile],
            ],
        ]);

        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());
    }

    public function testGeneratePdfSignalement(): void
    {
        $this->client->loginUser($this->user);

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        $signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2022-000000000001']);

        $route = $this->router->generate('back_signalement_gen_pdf', ['uuid' => $signalement->getUuid()]);
        $this->client->request('GET', $route);

        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());

        $redirectUrl = $this->client->getResponse()->headers->get('Location');
        /** @var Crawler $crawler */
        $crawler = $this->client->request('GET', $redirectUrl);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString(
            'L\'export pdf vous sera envoyé par e-mail',
            $crawler->filter('.fr-alert')->text()
        );
    }

    public function testAddFileSignalementNotDeny(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'user-13-05@signal-logement.fr']);
        $this->client->loginUser($user);

        $route = $this->router->generate('back_signalement_add_file', ['uuid' => '00000000-0000-0000-2023-000000000009']);
        $this->client->request('POST', $route);

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('/bo/signalements/00000000-0000-0000-2023-000000000009');
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
                '_token' => $this->generateCsrfToken($this->client, 'signalement_edit_file_'.$signalement->getId()),
            ]
        );

        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.fr-alert--success p', 'Le document a bien été modifié.');
    }

    public function testEditFileSignalementError(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2023-000000000009']);
        $route = $this->router->generate('back_signalement_edit_file', ['uuid' => $signalement->getUuid()]);

        $message = 'Je vais écrire un roman, lorem ipsum dolor sit amet, consectetur adipiscing elit.
        Nulla nec purus feugiat, ultricies nunc nec, tincidunt nunc. Nulla facilisi.
        Nullam nec... Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla nec purus feugiat, ultricies nunc nec, tincidunt nunc.
        Nulla facilisi. Nullam nec...';

        $this->client->request(
            'POST',
            $route,
            [
                'file_id' => $signalement->getFiles()[0]->getId(),
                'documentType' => 'AUTRE',
                'description' => $message,
                '_token' => $this->generateCsrfToken($this->client, 'signalement_edit_file_'.$signalement->getId()),
            ]
        );

        $this->assertResponseRedirects('/bo/signalements/'.$signalement->getUuid());
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.fr-alert--error p', 'La description ne doit pas dépasser 255 caractères');
    }
}
