<?php

namespace App\Repository\Query\Signalement;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class SignalementLocalisationQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>|int|string
     */
    public function findCities(User $user, ?Territory $territory = null): array|int|string
    {
        return $this->findCommunes($user, $territory, 'address.city', 'city');
    }

    /**
     * @return array<int, array<string, mixed>>|int|string
     */
    public function findZipcodes(User $user, ?Territory $territory = null): array|int|string
    {
        return $this->findCommunes($user, $territory, 'address.postCode', 'zipcode');
    }

    /**
     * @return array<int, array<string, mixed>>|int|string
     */
    public function findCommunes(
        User $user,
        ?Territory $territory = null,
        ?string $field = null,
        ?string $alias = null,
    ): array|int|string {
        $qb = $this->entityManager->createQueryBuilder()->from(Signalement::class, 's')
            ->select($field.' '.$alias)
            ->innerJoin('s.address', 'address')
            ->where('s.statut NOT IN (:statutList)')
            ->setParameter('statutList', SignalementStatus::excludedStatuses());
        if (!$user->isSuperAdmin() && !$user->isTerritoryAdmin()) {
            $qb->leftJoin('s.affectations', 'affectations')
                ->leftJoin('affectations.partner', 'partner')
                ->andWhere('partner IN (:partners)')
                ->setParameter('partners', $user->getPartners());
        }
        if ($territory) {
            $qb->andWhere('address.territory = :territory')
                ->setParameter('territory', $territory);
        } elseif (!$user->isSuperAdmin()) {
            $qb->andWhere('address.territory IN (:territories)')
                ->setParameter('territories', $user->getPartnersTerritories());
        }

        return $qb
            ->groupBy($field)
            ->orderBy($field, 'ASC')
            ->getQuery()
            ->getResult();
    }
}
