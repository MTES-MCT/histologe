<?php

namespace App\Tests\Functional\Controller;

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
    private ?Signalement $signalement = null;
    private ?User $user = null;
    private RouterInterface $router;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        /* @var Signalement $signalement */
        $this->signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2022-000000000001']);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $this->user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);

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

    public function testAddFailureFileSignalement()
    {
        $imageFile = new UploadedFile(
            __DIR__.'/../../files/sample.heic',
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

        $redirectUrl = $this->client->getResponse()->headers->get('Location');
        $crawler = $this->client->request('GET', $redirectUrl);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString(
            'Les fichiers de format HEIC/HEIF ne sont pas pris en charge',
            $crawler->filter('.fr-alert.fr-alert--error.fr-alert--sm')->text()
        );
    }

    public function testGeneratePdfSignalement()
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
            'L\'export pdf vous sera envoyé par email',
            $crawler->filter('.fr-alert')->text()
        );
    }
}
