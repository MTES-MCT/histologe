<?php

namespace App\Tests\Functional\Repository;

use App\Dto\Request\CalendarEventRequest;
use App\Entity\Enum\InterventionType;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Intervention;
use App\Entity\Partner;
use App\Entity\User;
use App\Repository\InterventionRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Repository\UserSignalementSubscriptionRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;

class InterventionRepositoryTest extends KernelTestCase
{
    public const string USER_ADMIN = 'admin-01@signal-logement.fr';
    public const string USER_ADMIN_TERRITORY_13 = 'admin-territoire-13-01@signal-logement.fr';
    public const string USER_ADMIN_TERRITORY_30 = 'admin-territoire-30@signal-logement.fr';
    public const string USER_PARTNER_13 = 'user-13-05@signal-logement.fr';

    private InterventionRepository $interventionRepository;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->interventionRepository = $container->get(InterventionRepository::class);
        $this->userRepository = $container->get(UserRepository::class);
    }

    public function testFindAllFilteredForUserSuperAdminReturnsOnlyDisplayableVisites(): void
    {
        $interventions = $this->findForUser(self::USER_ADMIN);

        $this->assertNotEmpty($interventions);
        $visiteTypes = [InterventionType::VISITE, InterventionType::VISITE_CONTROLE];
        foreach ($interventions as $intervention) {
            $this->assertContains($intervention->getType(), $visiteTypes);
            $this->assertNotSame(Intervention::STATUS_CANCELED, $intervention->getStatus());
            $this->assertNotContains($intervention->getSignalement()->getStatut(), SignalementStatus::excludedStatuses());
        }
    }

    public function testFindAllFilteredForUserSuperAdminWithTerritoryFilter(): void
    {
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = static::getContainer()->get(TerritoryRepository::class);
        $territory = $territoryRepository->findOneBy(['zip' => '13']);

        $interventions = $this->findForUser(self::USER_ADMIN, territoire: (string) $territory->getId());

        $this->assertNotEmpty($interventions);
        $this->assertLessThan(count($this->findForUser(self::USER_ADMIN)), count($interventions));
        foreach ($interventions as $intervention) {
            $this->assertSame('13', $intervention->getSignalement()->getAddress()->getTerritory()->getZip());
        }
    }

    public function testFindAllFilteredForUserTerritoryAdminSeesOnlyHisTerritory(): void
    {
        $interventions = $this->findForUser(self::USER_ADMIN_TERRITORY_13);

        $this->assertNotEmpty($interventions);
        foreach ($interventions as $intervention) {
            $this->assertSame('13', $intervention->getSignalement()->getAddress()->getTerritory()->getZip());
        }
    }

    public function testFindAllFilteredForUserTerritoryAdminWithPartnerOperatorFilter(): void
    {
        /** @var Partner $partner */
        $partner = $this->getUser(self::USER_PARTNER_13)->getPartners()->first();

        $interventions = $this->findForUser(self::USER_ADMIN_TERRITORY_13, operateurs: [(string) $partner?->getId()]);

        $this->assertNotEmpty($interventions);
        foreach ($interventions as $intervention) {
            $this->assertSame($partner?->getId(), $intervention->getPartner()?->getId());
        }
    }

    public function testFindAllFilteredForUserTerritoryAdminWithExternalOperatorFilter(): void
    {
        $interventions = $this->findForUser(self::USER_ADMIN_TERRITORY_30, operateurs: ['extern']);

        $this->assertNotEmpty($interventions);
        foreach ($interventions as $intervention) {
            $this->assertNotNull($intervention->getPartner());
            $this->assertSame($intervention->getExternalOperator(), $intervention->getPartner()->getNom());
            $this->assertSame(PartnerType::OPERATEUR_VISITES_ET_TRAVAUX, $intervention->getPartner()->getType());
        }
    }

    public function testFindAllFilteredForUserPartnerSeesOnlyHisPartnersVisites(): void
    {
        $user = $this->getUser(self::USER_PARTNER_13);
        $partnerIds = $user->getPartners()->map(static fn (Partner $partner): ?int => $partner->getId())->toArray();

        $interventions = $this->findForUser(self::USER_PARTNER_13);

        $this->assertNotEmpty($interventions);
        foreach ($interventions as $intervention) {
            $this->assertContains($intervention->getPartner()?->getId(), $partnerIds);
        }
    }

    public function testFindAllFilteredForUserOnlyMySignalements(): void
    {
        $user = $this->getUser(self::USER_ADMIN_TERRITORY_13);
        /** @var UserSignalementSubscriptionRepository $subscriptionRepository */
        $subscriptionRepository = static::getContainer()->get(UserSignalementSubscriptionRepository::class);

        $interventions = $this->findForUser(self::USER_ADMIN_TERRITORY_13, uniquementMesDossiers: '1');

        $this->assertLessThanOrEqual(count($this->findForUser(self::USER_ADMIN_TERRITORY_13)), count($interventions));
        foreach ($interventions as $intervention) {
            $subscription = $subscriptionRepository->findOneBy([
                'user' => $user,
                'signalement' => $intervention->getSignalement(),
            ]);
            $this->assertNotNull($subscription);
        }
    }

    public function testFindAllFilteredForUserExcludesEndDate(): void
    {
        // Visites des fixtures prévues le 2023-07-01 à 10h : fin exclue, la journée du 2023-07-01 n'est pas incluse
        $interventions = $this->findForUser(self::USER_ADMIN, dateDebut: '2023-06-30', dateFin: '2023-07-01');
        foreach ($interventions as $intervention) {
            $this->assertLessThan(new \DateTimeImmutable('2023-07-01'), $intervention->getScheduledAt());
        }

        $interventions = $this->findForUser(self::USER_ADMIN, dateDebut: '2023-07-01', dateFin: '2023-07-02');
        foreach ($interventions as $intervention) {
            $this->assertSame('2023-07-01', $intervention->getScheduledAt()->format('Y-m-d'));
        }
    }

    public function testFindNextForUserReturnsFirstUpcomingVisite(): void
    {
        // Visites des fixtures prévues le 2023-07-01 à 10h
        $nextIntervention = $this->createRepositoryAt('2023-06-25')->findNextForUser($this->getUser(self::USER_ADMIN));

        $upcomingInterventions = $this->findForUser(self::USER_ADMIN, dateDebut: '2023-06-25');
        $this->assertNotNull($nextIntervention);
        $this->assertSame($upcomingInterventions[0]->getId(), $nextIntervention->getId());
    }

    public function testFindNextForUserSuperAdminWithTerritoryFilter(): void
    {
        /** @var TerritoryRepository $territoryRepository */
        $territoryRepository = static::getContainer()->get(TerritoryRepository::class);
        $territory = $territoryRepository->findOneBy(['zip' => '13']);

        $nextIntervention = $this->createRepositoryAt('2000-01-01')
            ->findNextForUser($this->getUser(self::USER_ADMIN), $territory->getId());

        $this->assertNotNull($nextIntervention);
        $this->assertSame('13', $nextIntervention->getSignalement()->getAddress()->getTerritory()->getZip());
    }

    public function testFindNextForUserReturnsNullWithoutUpcomingVisite(): void
    {
        $nextIntervention = $this->createRepositoryAt('2100-01-01')->findNextForUser($this->getUser(self::USER_ADMIN));

        $this->assertNull($nextIntervention);
    }

    /**
     * Repository dont l'horloge est figée à la date donnée.
     */
    private function createRepositoryAt(string $date): InterventionRepository
    {
        /** @var ManagerRegistry $registry */
        $registry = static::getContainer()->get('doctrine');

        return new InterventionRepository($registry, new MockClock(new \DateTimeImmutable($date)));
    }

    private function getUser(string $email): User
    {
        /** @var User $user */
        $user = $this->userRepository->findOneBy(['email' => $email]);

        return $user;
    }

    /**
     * Toutes dates confondues par défaut.
     *
     * @param array<string>|null $operateurs
     *
     * @return array<int, Intervention>
     */
    private function findForUser(
        string $email,
        ?string $territoire = null,
        ?array $operateurs = null,
        ?string $uniquementMesDossiers = null,
        string $dateDebut = '2000-01-01',
        string $dateFin = '2100-01-01',
    ): array {
        return $this->interventionRepository->findAllFilteredForUser(
            $this->getUser($email),
            new CalendarEventRequest(
                territoire: $territoire,
                operateurs: $operateurs,
                dateDebut: $dateDebut,
                dateFin: $dateFin,
                uniquementMesDossiers: $uniquementMesDossiers,
            )
        );
    }
}
