<?php

namespace App\Repository\Query\SignalementList;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class SameAddressQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findSameAddressFiltered(User $user): array
    {
        $statusList = [
            SignalementStatus::ACTIVE,
            SignalementStatus::NEED_VALIDATION,
            SignalementStatus::CLOSED,
        ];

        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->innerJoin('s.address', 'address')
            ->select(
                's.id',
                's.uuid',
                's.createdAt',
                's.closedAt',
                's.reference',
                's.statut',
                'address.housenumber',
                'address.street',
                'address.postCode',
                'address.city',
                'address.cityCode',
                'address.point',
                's.nomOccupant',
                's.prenomOccupant',
                's.nomProprio',
                'IDENTITY(address.territory) AS territoryId',
            )
            ->where('s.statut IN (:statusList)')
            ->setParameter('statusList', $statusList)
            ->orderBy('address.housenumber', 'ASC')
            ->addOrderBy('address.postCode', 'ASC')
            ->addOrderBy('address.city', 'ASC')
            ->addOrderBy('s.createdAt', 'ASC');

        $queryDossiersMultiples = 'SELECT 1 FROM '.Signalement::class.' s2
                WHERE s2.address = s.address
                AND s2.statut IN (:statusList)
                AND s2.id != s.id';
        $qb->andWhere('EXISTS ('.$queryDossiersMultiples.')');

        if ($user->isSuperAdmin()) {
            // pas de restrictions pour les SA
        } elseif ($user->isTerritoryAdmin()) {
            $qb->andWhere('address.territory IN (:territories)')->setParameter('territories', $user->getPartnersTerritories());
        } else {
            $qb->leftJoin('s.affectations', 'affectations')
                ->leftJoin('affectations.partner', 'partner')
                ->andWhere('partner IN (:partners)')
                ->setParameter('partners', $user->getPartners());
        }

        return $qb->getQuery()->getArrayResult();
    }
}
