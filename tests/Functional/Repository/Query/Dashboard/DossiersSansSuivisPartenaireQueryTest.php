<?php

namespace App\Tests\Functional\Repository\Query\Dashboard;

use App\Entity\User;
use App\Repository\Query\Dashboard\DossiersSansSuivisPartenaireQuery;
use App\Repository\UserRepository;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DossiersSansSuivisPartenaireQueryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private DossiersSansSuivisPartenaireQuery $dossiersSansSuivisPartenaireQuery;

    private const string USER_PARTNER_TERRITORY_13 = 'user-13-01@signal-logement.fr';

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
        $this->dossiersSansSuivisPartenaireQuery = static::getContainer()->get(DossiersSansSuivisPartenaireQuery::class);
    }

    public function testSignalementsSansSuiviPartenaireDepuis60Jours(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => self::USER_PARTNER_TERRITORY_13]);

        $this->assertNotNull($user, 'User partenaire doit exister en base de test');

        // On fabrique des params simples (sans tri ni filtre particulier)
        $params = new TabQueryParameters();
        $params->partners = [];
        $params->mesDossiersAverifier = null;
        $params->queryCommune = null;

        $count = $this->dossiersSansSuivisPartenaireQuery->countSignalements($user, $params);
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);

        $ids = $this->dossiersSansSuivisPartenaireQuery->getSignalementsId($user, $params);
        $this->assertIsArray($ids);
        foreach ($ids as $id) {
            $this->assertIsInt($id);
        }

        $results = $this->dossiersSansSuivisPartenaireQuery->findSignalements($user, $params);
        $this->assertIsArray($results);

        foreach ($results as $row) {
            $this->assertArrayHasKey('id', $row);
            $this->assertArrayHasKey('uuid', $row);
            $this->assertArrayHasKey('reference', $row);
            $this->assertArrayHasKey('adresse', $row);
            $this->assertArrayHasKey('dernierSuiviAt', $row);
            $this->assertArrayHasKey('nbJoursDepuisDernierSuivi', $row);
        }
    }

    public function testFindSignalementsSansSuiviPartenaireAvecFiltreCommune(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);

        $user = $userRepository->findOneBy(['email' => self::USER_PARTNER_TERRITORY_13]);
        $params = new TabQueryParameters();
        $params->queryCommune = 'Marseille';

        $results = $this->dossiersSansSuivisPartenaireQuery->findSignalements($user, $params);
        $this->assertIsArray($results);
        foreach ($results as $row) {
            $this->assertStringContainsStringIgnoringCase('Marseille', $row['adresse']);
        }
    }
}
