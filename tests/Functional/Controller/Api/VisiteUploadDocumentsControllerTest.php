<?php

namespace App\Tests\Functional\Controller\Api;

use App\Entity\Enum\DocumentType;
use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Service\ImageManipulationHandler;
use App\Service\UploadHandlerService;
use App\Tests\ApiHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class VisiteUploadDocumentsControllerTest extends WebTestCase
{
    use ApiHelper;
    private KernelBrowser $client;
    private RouterInterface $router;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $user = self::getContainer()->get('doctrine')->getRepository(User::class)->findOneBy([
            'email' => 'api-01@signal-logement.fr',
        ]);

        $this->router = self::getContainer()->get('router');

        $this->client->loginUser($user, 'api');
    }

    public function testFileUploadSuccess(): void
    {
        $documentFile = new UploadedFile(
            __DIR__.'/../../../files/sample.pdf',
            'sample.pdf',
            'application/pdf',
            null,
            true
        );

        $imageManipulationHandler = $this->createMock(ImageManipulationHandler::class);
        self::getContainer()->set(ImageManipulationHandler::class, $imageManipulationHandler);

        $uploadHandlerServiceMock = $this->createMock(UploadHandlerService::class);
        $uploadHandlerServiceMock
            ->method('uploadFromFile')
            ->willReturnOnConsecutiveCalls('sample.jpg', '');

        $uploadHandlerServiceMock
            ->method('isFileSizeOk')
            ->willReturnOnConsecutiveCalls(true);

        self::getContainer()->set(UploadHandlerService::class, $uploadHandlerServiceMock);
        $uuidSignalement = '00000000-0000-0000-2023-000000000020';

        $signalement = self::getContainer()->get(SignalementRepository::class)->findOneBy([
            'uuid' => $uuidSignalement,
        ]);

        $uuidIntervention = $signalement->getInterventions()->first()->getUuid();
        $response = $this->postRequest($uuidIntervention, [$documentFile], 'rapport-visite');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $data = json_decode($response, true);
        $files = array_filter(
            $data['files'],
            fn ($file) => DocumentType::PROCEDURE_RAPPORT_DE_VISITE->value === $file['documentType']
        );

        $this->assertCount(1, $files);
        $this->hasXrequestIdHeaderAndOneApiRequestLog($this->client);
    }

    private function postRequest(string $uuid, array $files = [], ?string $typeDocumentVisite = null): false|string
    {
        $this->client->request(
            'POST',
            $this->router->generate(
                'api_visites_documents_visite_post', [
                    'uuid' => $uuid,
                    'typeDocumentVisite' => $typeDocumentVisite,
                ]),
            [],
            ['files' => $files],
            ['CONTENT_TYPE' => 'multipart/form-data']
        );

        return $this->client->getResponse()->getContent();
    }
}
