<?php

namespace App\Repository\Query\Dashboard;

use App\Dto\CountPartner;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\UserStatus;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use App\Entity\UserPartner;
use App\Repository\EmailDeliveryIssueRepository;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;

class KpiQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EmailDeliveryIssueRepository $emailDeliveryIssueRepository,
    ) {
    }

    public function countInjonctions(
        User $user,
        ?TabQueryParameters $params,
    ): int {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->where('s.statut = :statut')
            ->setParameter('statut', SignalementStatus::INJONCTION_BAILLEUR);

        $qb->select('COUNT(s.id)');

        if ($params?->territoireId) {
            $qb
                ->andWhere('s.territory = :territoireId')
                ->setParameter('territoireId', $params->territoireId);
        } elseif (!$user->isSuperAdmin()) {
            $qb
                ->andWhere('s.territory IN (:territories)')
                ->setParameter('territories', $user->getPartnersTerritories());
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array<int, Territory> $territories
     */
    public function countAgentsPbEmail(User $user, array $territories = []): int
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(User::class, 'u')
            ->leftJoin('u.userPartners', 'up')
            ->leftJoin('up.partner', 'p')
            ->andWhere('JSON_CONTAINS(u.roles, :roleUsager) = 0')
            ->setParameter('roleUsager', '"ROLE_USAGER"');

        if (\count($territories)) {
            $qb->andWhere('p.territory IN (:territories)')->setParameter('territories', $territories);
        } elseif (!$user->isSuperAdmin()) {
            $qb->andWhere('p.territory IN (:territories)')->setParameter('territories', $user->getPartnersTerritories());
        }

        $existsByEmailDql = $this->emailDeliveryIssueRepository->getExistsByEmailDql('u.email');

        $qb->andWhere($qb->expr()->exists($existsByEmailDql));
        $qb->select('COUNT(u.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param array<int, mixed> $territories
     */
    public function countPartnerNonNotifiables(array $territories): CountPartner
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->from(Partner::class, 'p')
            ->select('p.id');

        // Filtre sur les partenaires non notifiables
        $queryBuilder->addSelect(
            '(CASE
                WHEN (p.email IS NOT NULL AND p.email != \'\' AND p.emailNotifiable = 1) THEN 1
                WHEN EXISTS (
                    SELECT 1
                    FROM '.UserPartner::class.' up2
                    JOIN up2.user u2
                    WHERE up2.partner = p
                    AND u2.email IS NOT NULL
                    AND u2.statut LIKE \''.UserStatus::ACTIVE->value.'\'
                    AND u2.isMailingActive = 1
                ) THEN 1
                ELSE 0
            END) AS isNotifiable'
        );
        $queryBuilder->andHaving('isNotifiable = 0');

        $queryBuilder->andWhere('p.isArchive = 0');

        // Filtrer par territoires si précisé
        if (!empty($territories)) {
            $queryBuilder
                ->andWhere('p.territory IN (:territories)')
                ->setParameter('territories', $territories);
        }
        try {
            $count = count($queryBuilder->getQuery()->getSingleColumnResult());
        } catch (NonUniqueResultException) {
            $count = 0;
        }

        return new CountPartner((int) $count);
    }

    /**
     * @param array<int, mixed> $territories
     */
    public function countPartnerInterfaces(array $territories): int
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->from(Partner::class, 'p')
            ->select('COUNT(p.id)');

        $queryBuilder->andWhere('p.isArchive = 0');
        $queryBuilder->andWhere('p.isEsaboraActive = 1 or p.isIdossActive = 1');

        if (!empty($territories)) {
            $queryBuilder
                ->andWhere('p.territory IN (:territories)')
                ->setParameter('territories', $territories);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
