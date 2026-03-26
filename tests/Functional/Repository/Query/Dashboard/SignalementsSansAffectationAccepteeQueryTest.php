<?php

namespace App\Tests\Functional\Repository\Query\Dashboard;

use App\Entity\Signalement;
use App\Entity\User;
use App\Repository\Query\Dashboard\SignalementsSansAffectationAccepteeQuery;
use App\Repository\UserRepository;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SignalementsSansAffectationAccepteeQueryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private SignalementsSansAffectationAccepteeQuery $signalementsSansAffectationAccepteeQuery;
    private const string USER_PARTNER_TERRITORY_13 = 'user-13-01@signal-logement.fr';

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
        $this->userRepository = $this->entityManager->getRepository(User::class);
        $this->signalementsSansAffectationAccepteeQuery = static::getContainer()->get(SignalementsSansAffectationAccepteeQuery::class);
    }

    public function testCountSignalementsSansAffectationAcceptee(): void
    {
        $user = $this->userRepository->findOneBy(['email' => self::USER_PARTNER_TERRITORY_13]);
        $this->assertNotNull($user);

        $params = new TabQueryParameters();
        $params->partners = [];
        $params->mesDossiersAverifier = null;
        $params->queryCommune = null;

        $count = $this->signalementsSansAffectationAccepteeQuery->countSignalements($user, $params);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testFindSignalementsSansAffectationAcceptee(): void
    {
        $user = $this->userRepository->findOneBy(['email' => self::USER_PARTNER_TERRITORY_13]);

        $params = new TabQueryParameters();

        $results = $this->signalementsSansAffectationAccepteeQuery->findSignalements($user, $params);

        $this->assertIsArray($results);

        foreach ($results as $row) {
            $this->assertArrayHasKey('id', $row);
            $this->assertArrayHasKey('uuid', $row);
            $this->assertArrayHasKey('reference', $row);
            $this->assertArrayHasKey('adresse', $row);
            $this->assertArrayHasKey('parc', $row);
            $this->assertArrayHasKey('nbAffectations', $row);
            $this->assertArrayHasKey('lastAffectationAt', $row);
            $this->assertIsNumeric($row['nbAffectations']);
        }
    }

    public function testSignalementsSansAffectationAccepteeExclutStatutsActifs(): void
    {
        $user = $this->userRepository->findOneBy(['email' => self::USER_PARTNER_TERRITORY_13]);

        $params = new TabQueryParameters();

        $results = $this->signalementsSansAffectationAccepteeQuery->findSignalements($user, $params);

        foreach ($results as $row) {
            $signalement = $this->entityManager->getRepository(Signalement::class)->find($row['id']);

            foreach ($signalement->getAffectations() as $affectation) {
                $this->assertNotContains(
                    $affectation->getStatut(),
                    ['EN_COURS', 'FERME'],
                    'Un signalement retourné contient une affectation interdite'
                );
            }
        }
    }

    public function testFindSignalementsSansAffectationAccepteeSortByAffectedAt(): void
    {
        $user = $this->userRepository->findOneBy(['email' => self::USER_PARTNER_TERRITORY_13]);

        $params = new TabQueryParameters(
            sortBy: 'affectedAt',
            orderBy: 'DESC'
        );

        $results = $this->signalementsSansAffectationAccepteeQuery->findSignalements($user, $params);

        $dates = array_column($results, 'lastAffectationAt');

        $sorted = $dates;
        rsort($sorted);

        $this->assertEquals($sorted, $dates);
    }
}
