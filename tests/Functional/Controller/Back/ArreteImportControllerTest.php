<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\User;
use App\Service\Import\Arrete\ArreteImportHeader;
use App\Tests\FixturesHelper;
use App\Tests\SessionHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class ArreteImportControllerTest extends WebTestCase
{
    use FixturesHelper;
    use SessionHelper;

    private const string ROUTE_IMPORT_UPLOAD = '/bo/gerer-territoire/arretes/import-upload';
    private const string ROUTE_IMPORT_CONFIRM = '/bo/gerer-territoire/arretes/confirm';

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $_ENV['FEATURE_HISTO_ADDRESS'] = '1';
    }

    #[DataProvider('provideArreteImportFileContraints')]
    public function testImportUploadCsvWithArreteImportFileConstraints(
        string $content,
        string $filename,
        string $expectedErrorMessage,
    ): void {
        $client = static::createClient();
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        $csrfToken = $this->generateCsrfToken($client, 'import_arrete');

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_arrete_');
        file_put_contents($tmpFile, $content);

        $uploadedFile = new UploadedFile(
            $tmpFile,
            $filename,
            'text/csv',
            null,
            true,
        );

        $client->request(
            'POST',
            self::ROUTE_IMPORT_UPLOAD,
            [
                'import_arrete' => [
                    '_token' => $csrfToken,
                ],
            ],
            ['import_arrete' => ['file' => $uploadedFile]],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        /** @var string $content */
        $content = $response->getContent();
        $data = json_decode($content, true);

        $this->assertContains($expectedErrorMessage, $data['errors']);

        unlink($tmpFile);
    }

    public function testImportUploadCsvSuccessWithErrors(): void
    {
        $client = static::createClient();
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        $csrfToken = $this->generateCsrfToken($client, 'import_arrete');

        $uploadedFile = new UploadedFile(
            __DIR__.'/../../../files/arrete_import_test.csv',
            'arrete_import_test.csv',
            'text/csv',
            null,
            true
        );

        $client->request(
            'POST',
            self::ROUTE_IMPORT_UPLOAD,
            [
                'import_arrete' => [
                    '_token' => $csrfToken,
                ],
            ],
            ['import_arrete' => ['file' => $uploadedFile]],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        /** @var string $content */
        $content = $response->getContent();
        $data = json_decode($content, true);

        $this->assertArrayHasKey('errors', $data);
        $this->assertCount(1, $data['errors']);
        $this->assertStringContainsString('ligne 6', $data['errors'][0]);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(3, $data['data']);
    }

    public function testImportUploadCsvFullSuccess(): void
    {
        $client = static::createClient();
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        $csrfToken = $this->generateCsrfToken($client, 'import_arrete');

        $headers = [
            ArreteImportHeader::DATE_ARRETE,
            ArreteImportHeader::CLASSIFICATION_ARRETE,
            ArreteImportHeader::DATE_ARRETE_MAIN_LEVEE,
            ArreteImportHeader::NUMERO_VOIE,
            ArreteImportHeader::NOM_VOIE,
            ArreteImportHeader::CODE_POSTAL,
            ArreteImportHeader::COMMUNE,
            ArreteImportHeader::DENOMINATION_SYNDIC,
            ArreteImportHeader::ID_PARCELLAIRE,
        ];

        $content = "LIGNE 1\n"
            .implode(',', $headers)."\n"
            ."01/01/2023,Insalubrité,,8,Rue de la tourmentinerie,44850,Saint-Mars-du-Désert,,441860001AL0151\n"
            .'02/01/2023,Mise en sécurité procédure ordinaire,,8,Rue de la tourmentinerie,44850,Saint-Mars-du-Désert,,441860001AL0151';

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_arrete_success_');
        file_put_contents($tmpFile, $content);

        $uploadedFile = new UploadedFile(
            $tmpFile,
            'success.csv',
            'text/csv',
            null,
            true
        );

        $client->request(
            'POST',
            self::ROUTE_IMPORT_UPLOAD,
            [
                'import_arrete' => [
                    '_token' => $csrfToken,
                ],
            ],
            ['import_arrete' => ['file' => $uploadedFile]],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        /** @var string $content */
        $content = $response->getContent();
        $data = json_decode($content, true);

        $this->assertArrayNotHasKey('errors', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(2, $data['data']);

        unlink($tmpFile);
    }

    public function testImportConfirmFullSuccess(): void
    {
        $client = static::createClient();
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        $data = [
            [
                'numeroVoie' => '29',
                'nomVoie' => 'Rue Désirée Clary',
                'codePostal' => '13002',
                'commune' => 'Marseille',
                'identifiantParcellaire' => 'ID-PARCELLE-CONFIRM-1',
                'classificationArrete' => 'Insalubrité',
                'dateArrete' => '01/05/2024',
            ],
            [
                'numeroVoie' => '151',
                'nomVoie' => 'Avenue du Pont Trinquat',
                'codePostal' => '34070',
                'commune' => 'Montpellier',
                'identifiantParcellaire' => 'ID-PARCELLE-CONFIRM-2',
                'classificationArrete' => 'Mise en sécurité procédure urgente',
                'dateArrete' => '01/06/2024',
            ],
        ];

        $content = json_encode($data);
        $this->assertNotFalse($content);
        $client->request(
            'POST',
            self::ROUTE_IMPORT_CONFIRM,
            [],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest'],
            $content
        );

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $client->request('GET', '/bo/gerer-territoire/arretes/import');

        $this->assertSelectorTextContains('.fr-notice--success', '2 arrêtés ont été importés');
    }

    public function testImportConfirmSuccessWithErrors(): void
    {
        $client = static::createClient();
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);
        $client->loginUser($user);

        $headers = [
            'numeroVoie' => '151',
            'nomVoie' => 'Avenue du Pont Trinquat',
            'codePostal' => '34070',
            'commune' => 'Montpellier',
            'identifiantParcellaire' => 'ID-PARCELLE-DUPLICATE',
            'classificationArrete' => 'Insalubrité',
            'dateArrete' => '01/05/2024',
        ];

        $data = [
            [
                'numeroVoie' => '29',
                'nomVoie' => 'Rue Désirée Clary',
                'codePostal' => '13002',
                'commune' => 'Marseille',
                'identifiantParcellaire' => 'ID-PARCELLE-UNIQUE',
                'classificationArrete' => 'Insalubrité',
                'dateArrete' => '01/05/2024',
            ],
            $headers,
        ];

        $content = json_encode([$headers]);
        $this->assertIsString($content);
        $client->request('POST', self::ROUTE_IMPORT_CONFIRM, [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest'], $content);
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        $content = json_encode($data);
        $this->assertIsString($content);
        $client->request(
            'POST',
            self::ROUTE_IMPORT_CONFIRM,
            [],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest'],
            $content
        );

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        $client->request('GET', '/bo/gerer-territoire/arretes/import');

        $this->assertSelectorTextContains('.fr-notice--success', '1 arrêté a été importé.');
        $this->assertSelectorTextContains('.fr-notice--alert', 'a déjà été importé');
    }

    public static function provideArreteImportFileContraints(): \Generator
    {
        yield 'Missing headers' => [
            "date arrêté,classification arrêté\n01/01/2023,Insalubrité",
            'missing_headers.csv',
            'Le fichier CSV ne contient pas les colonnes attendues. Colonnes manquantes : numéro de voie, nom de la voie, code postal, commune, classification arrêté, date arrêté, identifiant parcellaire.',
        ];

        $headers = implode(',', ArreteImportHeader::REQUIRED_HEADERS);
        yield 'Empty file (only headers)' => [
            "PREMIERE LIGNE A IGNORER\n".$headers,
            'no_data.csv',
            'Le fichier CSV est vide ou ne contient pas de données.',
        ];

        $lines = "PREMIERE LIGNE A IGNORER\n".$headers."\n";
        for ($i = 0; $i < 51; ++$i) {
            $lines .= "8,Rue de la tourmentinerie,44850,Saint-Mars-du-Désert,Insalubrité,01/01/2023,12345-$i\n";
        }
        yield 'Too many lines' => [
            $lines,
            'too_many_lines.csv',
            'Le fichier CSV ne peut pas contenir plus de 50 lignes de données.',
        ];

        $duplicateData = "8,Rue de la tourmentinerie,44850,Saint-Mars-du-Désert,Insalubrité,01/01/2023,12345\n";
        $lines = "PREMIERE LIGNE A IGNORER\n".$headers."\n".$duplicateData.$duplicateData;
        yield 'Duplicate lines' => [
            $lines,
            'duplicate_lines.csv',
            'Le fichier CSV contient des lignes en doublon aux lignes : 4.',
        ];
    }
}
