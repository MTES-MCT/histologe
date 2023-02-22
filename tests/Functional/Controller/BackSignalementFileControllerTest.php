<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Signalement;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\RouterInterface;

class BackSignalementFileControllerTest extends WebTestCase
{
    use SessionHelper;

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

        $client = static::createClient();

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2022-000000000001']);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_signalement_add_file', ['uuid' => $signalement->getUuid()]);
        $client->request('POST', $route, [
            '_token' => $this->generateCsrfToken($client, 'signalement_add_file_'.$signalement->getId()),
        ], [
            'signalement-add-file' => [
                'photos' => [$imageFile],
                'documents' => [$documentFile],
            ],
        ]);

        $this->assertEquals(302, $client->getResponse()->getStatusCode());

        $redirectUrl = $client->getResponse()->headers->get('Location');
        $crawler = $client->request('GET', $redirectUrl);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
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

        $client = static::createClient();

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = static::getContainer()->get(SignalementRepository::class);
        /** @var Signalement $signalement */
        $signalement = $signalementRepository->findOneBy(['uuid' => '00000000-0000-0000-2022-000000000001']);

        /** @var UserRepository $userRepository */
        $userRepository = static::getContainer()->get(UserRepository::class);
        $user = $userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $client->loginUser($user);

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);

        $route = $router->generate('back_signalement_add_file', ['uuid' => $signalement->getUuid()]);
        $crawler = $client->request('POST', $route, [
            '_token' => $this->generateCsrfToken($client, 'signalement_add_file_'.$signalement->getId()),
        ], [
            'signalement-add-file' => [
                'photos' => [$imageFile],
            ],
        ]);

        $this->assertEquals(302, $client->getResponse()->getStatusCode());

        $redirectUrl = $client->getResponse()->headers->get('Location');
        $crawler = $client->request('GET', $redirectUrl);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString(
            'Les fichiers de format HEIC ne sont pas pris en charge',
            $crawler->filter('.fr-alert.fr-alert--error.fr-alert--sm')->text()
        );
    }
}
