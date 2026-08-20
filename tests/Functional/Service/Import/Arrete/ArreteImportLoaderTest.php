<?php

namespace App\Tests\Functional\Service\Import\Arrete;

use App\Entity\Arrete;
use App\Entity\User;
use App\Service\Gouv\Ban\AddressService;
use App\Service\Import\Arrete\ArreteImportLoader;
use App\Service\Import\Arrete\ArreteImportRow;
use App\Tests\Fake\AddressServiceFake;
use App\Tests\FixturesHelper;
use LongitudeOne\Spatial\Exception\InvalidValueException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ArreteImportLoaderTest extends WebTestCase
{
    use FixturesHelper;

    public function testValidate(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $addressServiceFake = new AddressServiceFake();
        $container->set(AddressService::class, $addressServiceFake);

        /** @var ArreteImportLoader $arreteImportLoader */
        $arreteImportLoader = $container->get(ArreteImportLoader::class);

        $filepath = __DIR__.'/../../../../files/arrete_import_test.csv';

        $entityManager = $container->get('doctrine.orm.entity_manager');
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);

        [$errors, $validRows] = $arreteImportLoader->validate($filepath, $user);
        $this->assertCount(4, $errors);
        $this->assertStringContainsString('1 ligne présentent une erreur de format', $errors[0]);
        $this->assertStringContainsString('ligne 6', $errors[0]);
        $this->assertEquals('Ligne 6 : Le nom de la voie est obligatoire.', $errors[1]);
        $this->assertEquals('Ligne 6 : Le code postal est obligatoire.', $errors[2]);
        $this->assertEquals('Ligne 6 : La commune est obligatoire.', $errors[3]);

        $this->assertCount(3, $validRows, 'Il devrait y avoir 3 lignes valides.');

        $row1 = $validRows[0];
        $this->assertEquals('8', $row1->getNumeroVoie());
        $this->assertEquals('Rue de la tourmentinerie', $row1->getNomVoie());
        $this->assertEquals('44850', $row1->getCodePostal());

        $row2 = $validRows[1];
        $this->assertEquals('10', $row2->getNumeroVoie());
        $this->assertEquals('Rue de la Paix', $row2->getNomVoie());
        $this->assertEquals('75002', $row2->getCodePostal());

        $row3 = $validRows[2];
        $this->assertEquals('8', $row3->getNumeroVoie());
        $this->assertEquals('Rue de la tourmentinerie', $row3->getNomVoie());
        $this->assertEquals('44850', $row3->getCodePostal());
        $this->assertNotNull($row3->getDateArreteMainLevee());
    }

    /**
     * @throws InvalidValueException
     */
    public function testLoad(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $addressServiceFake = new AddressServiceFake();
        $container->set(AddressService::class, $addressServiceFake);

        /** @var ArreteImportLoader $arreteImportLoader */
        $arreteImportLoader = $container->get(ArreteImportLoader::class);

        $entityManager = $container->get('doctrine.orm.entity_manager');
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);

        $validRows = [
            new ArreteImportRow()
                ->setNumeroVoie('5')
                ->setNomVoie('Rue Basse')
                ->setCodePostal('44350')
                ->setCommune('Guérande')
                ->setIdentifiantParcellaire('ID-PARCELLE-001')
                ->setClassificationArrete('Impropre')
                ->setDateArrete(new \DateTimeImmutable('2024-01-01')),
            new ArreteImportRow()
                ->setNumeroVoie('151')
                ->setNomVoie('Avenue du Pont Trinquat')
                ->setCodePostal('34070')
                ->setCommune('Montpellier')
                ->setIdentifiantParcellaire('ID-PARCELLE-002')
                ->setClassificationArrete('Insalubrité & Saturnisme')
                ->setDateArrete(new \DateTimeImmutable('2024-02-01')),
        ];

        $arretes = $arreteImportLoader->load($validRows, $user);
        $metadata = $arreteImportLoader->getMetadata();

        $this->assertCount(2, $arretes);
        $this->assertEquals(2, $metadata['countSuccess']);
    }

    /**
     * @throws InvalidValueException
     */
    public function testLoadWithExistingArrete(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $addressServiceFake = new AddressServiceFake();
        $container->set(AddressService::class, $addressServiceFake);

        /** @var ArreteImportLoader $arreteImportLoader */
        $arreteImportLoader = $container->get(ArreteImportLoader::class);

        $entityManager = $container->get('doctrine.orm.entity_manager');
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);

        $validRows = [
            // Cet arrêté existe déjà dans Arrete.yml
            new ArreteImportRow()
                ->setNumeroVoie('5')
                ->setNomVoie('Rue basse')
                ->setCodePostal('30360')
                ->setCommune('Vézénobres')
                ->setIdentifiantParcellaire('123456789')
                ->setClassificationArrete('Usage non approprié')
                ->setDateArrete(new \DateTimeImmutable('2025-04-13')),
            // Nouveau
            new ArreteImportRow()
                ->setNumeroVoie('29')
                ->setNomVoie('Rue Désirée Clary')
                ->setCodePostal('13002')
                ->setCommune('Marseille')
                ->setIdentifiantParcellaire('12345678')
                ->setClassificationArrete('Mise en sécurité procédure urgente')
                ->setDateArrete(new \DateTimeImmutable('2024-03-01')),
        ];

        $arretes = $arreteImportLoader->load($validRows, $user);

        $metadata = $arreteImportLoader->getMetadata();
        $this->assertCount(1, $arretes);
        $this->assertEquals(1, $metadata['countSuccess']);
        $this->assertCount(1, $metadata['errors']);
        $this->assertStringContainsString('a déjà été importé', $metadata['errors'][0]);
    }

    /**
     * @throws InvalidValueException
     */
    public function testLoadWithAdminTerritoryInvalidTerritory(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $addressServiceFake = new AddressServiceFake();
        $container->set(AddressService::class, $addressServiceFake);

        /** @var ArreteImportLoader $arreteImportLoader */
        $arreteImportLoader = $container->get(ArreteImportLoader::class);

        $entityManager = $container->get('doctrine.orm.entity_manager');
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-territoire-13-01@signal-logement.fr']);

        $validRows = [
            // Cet arrêté est au 30360 Vézénobres (Territoire 30)
            new ArreteImportRow()
                ->setNumeroVoie('5')
                ->setNomVoie('Rue basse')
                ->setCodePostal('30360')
                ->setCommune('Vézénobres')
                ->setIdentifiantParcellaire('ID-PARCELLE-TEST-TERRITORY')
                ->setClassificationArrete('Usage non approprié')
                ->setDateArrete(new \DateTimeImmutable('2025-04-13')),
        ];

        $arretes = $arreteImportLoader->load($validRows, $user);
        $metadata = $arreteImportLoader->getMetadata();

        $this->assertCount(0, $arretes);
        $this->assertEquals(0, $metadata['countSuccess']);
        $this->assertCount(1, $metadata['errors']);
        $this->assertStringContainsString('ne peut pas être importé', $metadata['errors'][0]);
        $this->assertStringContainsString('5 Rue basse 30360 Vézénobres', $metadata['errors'][0]);
    }

    /**
     * @throws InvalidValueException
     */
    public function testLoadWithDuplicateData(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $addressServiceFake = new AddressServiceFake();
        $container->set(AddressService::class, $addressServiceFake);

        /** @var ArreteImportLoader $arreteImportLoader */
        $arreteImportLoader = $container->get(ArreteImportLoader::class);

        $entityManager = $container->get('doctrine.orm.entity_manager');
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);

        $validRows = [
            new ArreteImportRow()
                ->setNumeroVoie('29')
                ->setNomVoie('Rue Désirée Clary')
                ->setCodePostal('13002')
                ->setCommune('Marseille')
                ->setIdentifiantParcellaire('12345678')
                ->setClassificationArrete('Mise en sécurité procédure urgente')
                ->setDateArrete(new \DateTimeImmutable('2024-03-01')),
            // Nouveau
            new ArreteImportRow()
                ->setNumeroVoie('29')
                ->setNomVoie('Rue Désirée Clary')
                ->setCodePostal('13002')
                ->setCommune('Marseille')
                ->setIdentifiantParcellaire('12345678')
                ->setClassificationArrete('Mise en sécurité procédure urgente')
                ->setDateArrete(new \DateTimeImmutable('2024-03-01')),
        ];

        $arretes = $arreteImportLoader->load($validRows, $user);

        $metadata = $arreteImportLoader->getMetadata();
        $this->assertCount(1, $arretes);
        $this->assertEquals(1, $metadata['countSuccess']);
        $this->assertCount(1, $metadata['errors']);
        $this->assertStringContainsString('a déjà été importé', $metadata['errors'][0]);
    }

    /**
     * @throws InvalidValueException
     */
    public function testLoadWithInvalidAddress(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $addressServiceFake = new AddressServiceFake();
        $container->set(AddressService::class, $addressServiceFake);

        /** @var ArreteImportLoader $arreteImportLoader */
        $arreteImportLoader = $container->get(ArreteImportLoader::class);

        $entityManager = $container->get('doctrine.orm.entity_manager');
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);

        $validRows = [
            new ArreteImportRow()
                ->setNumeroVoie('1343')
                ->setNomVoie('Rue Désirée Doué')
                ->setCodePostal('13002')
                ->setCommune('Marseille')
                ->setIdentifiantParcellaire('12345678')
                ->setClassificationArrete('Mise en sécurité procédure urgente')
                ->setDateArrete(new \DateTimeImmutable('2024-03-01')),
        ];

        $arretes = $arreteImportLoader->load($validRows, $user);

        $metadata = $arreteImportLoader->getMetadata();
        $this->assertCount(0, $arretes);
        $this->assertEquals(0, $metadata['countSuccess']);
        $this->assertCount(1, $metadata['errors']);
        $this->assertStringContainsString('ne peut pas être importé', $metadata['errors'][0]);
    }

    /**
     * @throws InvalidValueException
     */
    public function testLoadWithLowBanScoreAndRnbId(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $addressServiceFake = new AddressServiceFake();
        $container->set(AddressService::class, $addressServiceFake);

        /** @var ArreteImportLoader $arreteImportLoader */
        $arreteImportLoader = $container->get(ArreteImportLoader::class);

        $entityManager = $container->get('doctrine.orm.entity_manager');
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'admin-01@signal-logement.fr']);

        $validRows = [
            new ArreteImportRow()
                ->setNomVoie('Chemin du grand méchant loup')
                ->setCodePostal('30360')
                ->setCommune('Vézénobres')
                ->setIdentifiantParcellaire('ID-PARCELLE-RNB-1')
                ->setClassificationArrete('Insalubrité')
                ->setDateArrete(new \DateTimeImmutable('2024-03-01'))
                ->setRnbId('Y1QC6FM9XXGS'),
        ];

        $arretes = $arreteImportLoader->load($validRows, $user);

        $metadata = $arreteImportLoader->getMetadata();
        $this->assertCount(1, $arretes);
        $this->assertEquals(1, $metadata['countSuccess']);
    }
}
