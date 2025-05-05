<?php

namespace App\Tests\Functional\Service\Import\GridAffectation;

use App\Entity\Territory;
use App\Factory\PartnerFactory;
use App\Factory\UserFactory;
use App\Manager\ManagerInterface;
use App\Manager\PartnerManager;
use App\Manager\UserManager;
use App\Service\Import\GridAffectation\GridAffectationLoader;
use App\Tests\FixturesHelper;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GridAffectationLoaderTest extends KernelTestCase
{
    use FixturesHelper;

    public const string FIXTURE_PARTNER_DDT = 'DDT/M';
    public const string FIXTURE_PARTNER_ARS = 'ARS';
    public const string FIXTURE_PARTNER_SCHS = 'Commune / SCHS';
    public const string FIXTURE_PARTNER_ADIL = 'ADIL';
    public const string FIXTURE_PARTNER_EPCI = 'EPCI';
    public const string FIXTURE_PARTNER_FAKE = 'Random Type';

    public const string FIXTURE_PARTNER_DDT_EMAIL = 'ddt-m@signal-logement.fr';
    public const string FIXTURE_PARTNER_ARS_EMAIL = 'ars@signal-logement.fr';

    public const string FIXTURE_USER_EMAIL_DUPLICATE = 'user-ddt@signal-logement.fr';
    public const string FIXTURE_ROLE_RT = 'Resp. Territoire';
    public const string FIXTURE_ROLE_PARTNER = 'Admin. partenaire';
    public const string FIXTURE_ROLE_USER = 'Agent';

    private GridAffectationLoader $gridAffectationLoader;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->gridAffectationLoader = new GridAffectationLoader(
            self::getContainer()->get(PartnerFactory::class),
            self::getContainer()->get(PartnerManager::class),
            self::getContainer()->get(UserFactory::class),
            self::getContainer()->get(UserManager::class),
            self::getContainer()->get(ManagerInterface::class),
            self::getContainer()->get(ValidatorInterface::class),
            self::getContainer()->get(LoggerInterface::class),
            $this->entityManager
        );
    }

    public function testLoadValidPartnersAndUserInCreateMode(): void
    {
        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['isActive' => 0]);
        $errors = $this->gridAffectationLoader->validate($this->provideValidData(), $territory);
        $this->assertCount(0, $errors);

        $this->gridAffectationLoader->load($territory, $this->provideValidData(), []);

        $metaData = $this->gridAffectationLoader->getMetadata();
        $this->assertEquals(3, $metaData['nb_partners']);
        $this->assertEquals(4, $metaData['nb_users_created']);
        $this->assertEmpty($metaData['errors'], 'Grid has no errors.');
    }

    public function testLoadValidPartnerAndUserInUpdateMode(): void
    {
        $faker = Factory::create();
        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => 13]);
        $data[] = [
            'Institution' => self::FIXTURE_PARTNER_ARS,
            'Type' => self::FIXTURE_PARTNER_ARS,
            'Codes insee' => '',
            'Prénom' => $faker->firstName(),
            'Nom' => $faker->lastName(),
            'E-mail' => 'user-13-06@signal-logement.fr',
            "E-mail d'équipe" => self::FIXTURE_PARTNER_ARS_EMAIL,
            'Rôle' => self::FIXTURE_ROLE_PARTNER,
        ];

        $data[] = [
            'Institution' => self::FIXTURE_PARTNER_SCHS,
            'Type' => self::FIXTURE_PARTNER_SCHS,
            'Codes insee' => '',
            'Prénom' => $faker->firstName(),
            'Nom' => $faker->lastName(),
            'E-mail' => 'sara.conor@signal-logement.fr',
            "E-mail d'équipe" => 'partenaire-13-01@signal-logement.fr',
            'Rôle' => self::FIXTURE_ROLE_PARTNER,
        ];

        $data[] = [
            'Institution' => self::FIXTURE_PARTNER_ARS,
            'Type' => self::FIXTURE_PARTNER_ARS,
            'Codes insee' => '',
            'Prénom' => 'Arnold',
            'Nom' => 'Scharwz',
            'E-mail' => 'arnold.sch@signal-logement.fr',
            "E-mail d'équipe" => self::FIXTURE_PARTNER_ARS_EMAIL,
            'Rôle' => self::FIXTURE_ROLE_USER,
        ];

        $errors = $this->gridAffectationLoader->validate($data, $territory, true);
        $this->assertCount(0, $errors);
        $this->gridAffectationLoader->load($territory, $data, []);
        $metaData = $this->gridAffectationLoader->getMetadata();

        $this->assertEquals(1, $metaData['nb_partners'], 'Partner ARS added');
        $this->assertEquals(1, $metaData['nb_users_created'], 'arnold.sch@signal-logement.fr');
        $this->assertCount(
            2,
            $metaData['errors'],
            'user-13-06@signal-logement.fr already exists and Partner e-mails exists partenaire-13-01@signal-logement.fr'
        );
    }

    public function testValidateWithErrors(): void
    {
        $territory = $this->entityManager->getRepository(Territory::class)->findOneBy(['zip' => 13]);

        $errors = [
            'line 3 : E-mail incorrect pour un partenaire : arssignal-logement.fr',
            'line 5 : Type incorrect pour Random Type --> Random Type',
            'line 5 : Rôle incorrect pour jon.conor@signal-logement.fr --> Fake role',
            'line 6 : Type incorrect pour Random Type --> Random Type',
            'line 6 : E-mail incorrect pour un utilisateur : john.doe@',
            'line 7 : E-mail partenaire déjà existant dans le territoire avec (partenaire-13-01@signal-logement.fr) dans Bouches-du-Rhône, nom : Partenaire 13-01',
            'line 8 : E-mail manquant pour Margaretta Borer, partenaire ADIL',
            'line 9 : Nom de partenaire manquant',
            'line 10 : Utilisateur déjà existant avec (user-13-06@signal-logement.fr) dans Bouches-du-Rhône, partenaire : Partenaire 13-06 ESABORA ARS, rôle : Agent',
            'Certains partenaires ont un e-mail en commun ddt-m@signal-logement.fr',
            'Certains utilisateurs ont un e-mail en commun user-ddt@signal-logement.fr',
            'Certains utilisateurs ont un e-mail en commun avec un partenaire ddt-m@signal-logement.fr,user-ddt@signal-logement.fr',
        ];

        $this->assertEquals(
            $errors,
            $this->gridAffectationLoader->validate($this->provideInvalidDataWithDuplicatePartnersAndUsers(), $territory)
        );
    }

    public function provideValidData(): array
    {
        $faker = Factory::create();

        return [
            [
                'Institution' => self::FIXTURE_PARTNER_DDT,
                'Type' => self::FIXTURE_PARTNER_DDT,
                'Codes insee' => '',
                'Prénom' => $faker->firstName(),
                'Nom' => $faker->lastName(),
                'E-mail' => $faker->email(),
                "E-mail d'équipe" => self::FIXTURE_PARTNER_DDT_EMAIL,
                'Rôle' => self::FIXTURE_ROLE_RT,
            ],
            [
                'Institution' => self::FIXTURE_PARTNER_ARS,
                'Type' => self::FIXTURE_PARTNER_ARS,
                'Codes insee' => '',
                'Prénom' => $faker->firstName(),
                'Nom' => $faker->lastName(),
                'E-mail' => $faker->email(),
                "E-mail d'équipe" => self::FIXTURE_PARTNER_ARS_EMAIL,
                'Rôle' => self::FIXTURE_ROLE_USER,
            ],
            [
                'Institution' => self::FIXTURE_PARTNER_ARS,
                'Type' => self::FIXTURE_PARTNER_ARS,
                'Codes insee' => '',
                'Prénom' => $faker->firstName(),
                'Nom' => $faker->lastName(),
                'E-mail' => $faker->email(),
                "E-mail d'équipe" => self::FIXTURE_PARTNER_ARS_EMAIL,
                'Rôle' => self::FIXTURE_ROLE_USER,
            ],
            [
                'Institution' => self::FIXTURE_PARTNER_SCHS,
                'Type' => self::FIXTURE_PARTNER_SCHS,
                'Codes insee' => '01000',
                'Prénom' => $faker->firstName(),
                'Nom' => $faker->lastName(),
                'E-mail' => $faker->email(),
                "E-mail d'équipe" => 'schs@signal-logement.fr',
                'Rôle' => self::FIXTURE_ROLE_USER,
            ],
        ];
    }

    public function provideInvalidDataWithDuplicatePartnersAndUsers(): array
    {
        $faker = Factory::create();

        return [
            [
                'Institution' => self::FIXTURE_PARTNER_DDT,
                'Type' => self::FIXTURE_PARTNER_DDT,
                'Codes insee' => '',
                'Prénom' => $faker->firstName(),
                'Nom' => $faker->lastName(),
                'E-mail' => self::FIXTURE_USER_EMAIL_DUPLICATE,
                "E-mail d'équipe" => self::FIXTURE_PARTNER_DDT_EMAIL,
                'Rôle' => self::FIXTURE_ROLE_RT,
            ],
            [
                'Institution' => self::FIXTURE_PARTNER_ARS,
                'Type' => self::FIXTURE_PARTNER_ARS,
                'Codes insee' => '',
                'Prénom' => $faker->firstName(),
                'Nom' => $faker->lastName(),
                'E-mail' => self::FIXTURE_USER_EMAIL_DUPLICATE,
                "E-mail d'équipe" => 'arssignal-logement.fr',
                'Rôle' => self::FIXTURE_ROLE_RT,
            ],
            [
                'Institution' => self::FIXTURE_PARTNER_SCHS,
                'Type' => self::FIXTURE_PARTNER_SCHS,
                'Codes insee' => '',
                'Prénom' => $faker->firstName(),
                'Nom' => $faker->lastName(),
                'E-mail' => self::FIXTURE_USER_EMAIL_DUPLICATE,
                "E-mail d'équipe" => self::FIXTURE_PARTNER_DDT_EMAIL,
                'Rôle' => self::FIXTURE_ROLE_RT,
            ],
            [
                'Institution' => self::FIXTURE_PARTNER_FAKE,
                'Type' => self::FIXTURE_PARTNER_FAKE,
                'Codes insee' => '',
                'Prénom' => $faker->firstName(),
                'Nom' => $faker->lastName(),
                'E-mail' => 'jon.conor@signal-logement.fr',
                "E-mail d'équipe" => $faker->companyEmail(),
                'Rôle' => 'Fake role',
            ],
            [
                'Institution' => self::FIXTURE_PARTNER_FAKE,
                'Type' => self::FIXTURE_PARTNER_FAKE,
                'Codes insee' => '',
                'Prénom' => $faker->firstName(),
                'Nom' => $faker->lastName(),
                'E-mail' => 'john.doe@',
                "E-mail d'équipe" => $faker->companyEmail(),
                'Rôle' => self::FIXTURE_ROLE_PARTNER,
            ],
            [
                'Institution' => self::FIXTURE_PARTNER_ADIL,
                'Type' => self::FIXTURE_PARTNER_ADIL,
                'Codes insee' => '',
                'Prénom' => $faker->firstName(),
                'Nom' => $faker->lastName(),
                'E-mail' => 'sara.conor@signal-logement.fr',
                "E-mail d'équipe" => 'partenaire-13-01@signal-logement.fr',
                'Rôle' => self::FIXTURE_ROLE_PARTNER,
            ],
            [
                'Institution' => self::FIXTURE_PARTNER_ADIL,
                'Type' => self::FIXTURE_PARTNER_ADIL,
                'Codes insee' => '',
                'Prénom' => 'Margaretta',
                'Nom' => 'Borer',
                'E-mail' => '',
                "E-mail d'équipe" => $faker->companyEmail(),
                'Rôle' => self::FIXTURE_ROLE_PARTNER,
            ],
            [
                'Institution' => '',
                'Type' => self::FIXTURE_PARTNER_ADIL,
                'Codes insee' => '',
                'Prénom' => 'Margaretta',
                'Nom' => 'Borer',
                'E-mail' => $faker->email(),
                "E-mail d'équipe" => $faker->companyEmail(),
                'Rôle' => self::FIXTURE_ROLE_PARTNER,
            ],
            [
                'Institution' => self::FIXTURE_PARTNER_EPCI,
                'Type' => self::FIXTURE_PARTNER_EPCI,
                'Codes insee' => '',
                'Prénom' => $faker->firstName(),
                'Nom' => $faker->lastName(),
                'E-mail' => 'user-13-06@signal-logement.fr',
                "E-mail d'équipe" => $faker->companyEmail(),
                'Rôle' => self::FIXTURE_ROLE_PARTNER,
            ],
        ];
    }
}
