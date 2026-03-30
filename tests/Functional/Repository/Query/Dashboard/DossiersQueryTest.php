<?php

namespace App\Tests\Functional\Repository\Query\Dashboard;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\CreationSource;
use App\Entity\Enum\SignalementStatus;
use App\Entity\User;
use App\Repository\Query\Dashboard\DossiersQuery;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class DossiersQueryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private DossiersQuery $dossiersQuery;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        /** @var ManagerRegistry $doctrine */
        $doctrine = $kernel->getContainer()->get('doctrine');

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $doctrine->getManager();

        $this->entityManager = $entityManager;
        $this->dossiersQuery = static::getContainer()->get(DossiersQuery::class);
    }

    public function testFindNewDossiersFromFormulaireUsager(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        static::getContainer()->get('security.token_storage')->setToken($token);

        $tabQueryParameter = new TabQueryParameters(
            createdFrom: CreationSource::CREATED_FROM_FORMULAIRE_USAGER,
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );

        $dossiers = $this->dossiersQuery->findNewDossiersFrom(
            signalementStatus: SignalementStatus::NEED_VALIDATION,
            tabQueryParameters: $tabQueryParameter,
        );

        foreach ($dossiers as $dossier) {
            $this->assertNotNull($dossier->uuid);
            $this->assertNotNull($dossier->profilDeclarant);
            $this->assertNotNull($dossier->nomOccupant);
            $this->assertNotNull($dossier->prenomOccupant);
            $this->assertNotNull($dossier->reference);
            $this->assertNotNull($dossier->adresse);
            $this->assertNotNull($dossier->depotAt);
            $this->assertEquals('', $dossier->depotBy);
        }
    }

    public function testFindNewDossiersFromFormulairePro(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        static::getContainer()->get('security.token_storage')->setToken($token);

        $tabQueryParameter = new TabQueryParameters(
            createdFrom: CreationSource::CREATED_FROM_FORMULAIRE_PRO,
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );

        $dossiers = $this->dossiersQuery->findNewDossiersFrom(
            signalementStatus: SignalementStatus::NEED_VALIDATION,
            tabQueryParameters: $tabQueryParameter,
        );

        foreach ($dossiers as $dossier) {
            $this->assertNotNull($dossier->uuid);
            $this->assertNotNull($dossier->profilDeclarant);
            $this->assertNotNull($dossier->nomOccupant);
            $this->assertNotNull($dossier->prenomOccupant);
            $this->assertNotNull($dossier->reference);
            $this->assertNotNull($dossier->adresse);
            $this->assertNotNull($dossier->depotAt);
            $this->assertNotNull($dossier->depotBy);
        }
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function testCountNewDossiersFromFormulaireUsager(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        static::getContainer()->get('security.token_storage')->setToken($token);

        $tabQueryParameter = new TabQueryParameters(
            createdFrom: CreationSource::CREATED_FROM_FORMULAIRE_USAGER,
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );

        $countDossiers = $this->dossiersQuery->countNewDossiersFrom(
            signalementStatus: SignalementStatus::NEED_VALIDATION,
            tabQueryParameters: $tabQueryParameter
        );
        $this->assertEquals(8, $countDossiers);
    }

    public function testCountAndFindDossiersFermePartenaireCommune(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        static::getContainer()->get('security.token_storage')->setToken($token);

        // Ferleture des affectations
        $affRepo = $this->entityManager->getRepository(Affectation::class);
        $affectations = $affRepo->createQueryBuilder('a')
            ->innerJoin('a.signalement', 's')
            ->innerJoin('a.partner', 'p')
            ->where('s.reference IN (:refs)')
            ->andWhere('p.nom = :partnerName')
            ->setParameter('refs', ['2023-15', '2023-14'])
            ->setParameter('partnerName', 'Partenaire 13-05 ESABORA SCHS')
            ->getQuery()
            ->getResult();
        foreach ($affectations as $aff) {
            $aff->setStatut(AffectationStatus::CLOSED);
            $aff->setAnsweredAt(new \DateTimeImmutable());
        }

        $this->entityManager->flush();

        $tabQueryParameter = new TabQueryParameters(
            sortBy: 'createdAt',
            orderBy: 'DESC',
        );

        $count = $this->dossiersQuery->countDossiersFermePartenaireCommune(tabQueryParameters: $tabQueryParameter);
        $this->assertIsInt($count);
        $this->assertEquals(2, $count);

        $result = $this->dossiersQuery->findDossiersFermePartenaireCommune(tabQueryParameters: $tabQueryParameter);
        $this->assertIsArray($result);
        $this->assertEquals(2, count($result));
        foreach ($result as $dossier) {
            $this->assertNotNull($dossier->uuid);
            $this->assertNotNull($dossier->reference);
        }
    }
}
