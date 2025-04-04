<?php

namespace App\Tests\Functional\Controller;

use App\Controller\FileController;
use App\Repository\FileRepository;
use CoopTilleuls\UrlSignerBundle\UrlSigner\UrlSignerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class FileControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private RouterInterface $router;
    private FileRepository $fileRepository;
    private UrlSignerInterface $urlSigner;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        /* @var RouterInterface $router */
        $this->router = self::getContainer()->get(RouterInterface::class);
        /* @var FileRepository $fileRepository */
        $this->fileRepository = static::getContainer()->get(FileRepository::class);
        /* @var UrlSignerInterface $urlSigner */
        $this->urlSigner = static::getContainer()->get(UrlSignerInterface::class);
    }

    public function testShowFileSigned(): void
    {
        $file = $this->fileRepository->findOneBy(['filename' => 'test1.23.pdf']);
        $url = $this->router->generate('show_file', ['uuid' => $file->getUuid()]);
        $url = $this->urlSigner->sign($url, FileController::SIGNATURE_VALIDITY_DURATION);

        $this->client->request('GET', $url);

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testShowUnsigned(): void
    {
        $file = $this->fileRepository->findOneBy(['filename' => 'test1.23.pdf']);
        $url = $this->router->generate('show_file', ['uuid' => $file->getUuid()]);

        $this->client->request('GET', $url);

        $this->assertEquals(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
    }
}
