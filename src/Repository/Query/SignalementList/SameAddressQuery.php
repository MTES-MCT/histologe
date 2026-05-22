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
            ->select(
                's.id',
                's.uuid',
                's.createdAt',
                's.closedAt',
                's.reference',
                's.statut',
                's.adresseOccupant',
                's.cpOccupant',
                's.villeOccupant',
                's.geoloc',
                's.nomOccupant',
                's.prenomOccupant',
                's.nomProprio',
                'IDENTITY(s.territory) AS territoryId',
            )
            ->where('EXISTS (
                SELECT 1 FROM '.Signalement::class.' s2
                WHERE s2.adresseOccupant = s.adresseOccupant
                AND s2.cpOccupant = s.cpOccupant
                AND s2.villeOccupant = s.villeOccupant
                AND s2.statut IN (:statusList)
                AND s2.id != s.id
            )')
            ->andWhere('s.statut IN (:statusList)')
            ->setParameter('statusList', $statusList)
            ->orderBy('s.adresseOccupant', 'ASC')
            ->addOrderBy('s.cpOccupant', 'ASC')
            ->addOrderBy('s.villeOccupant', 'ASC')
            ->addOrderBy('s.createdAt', 'ASC');

        if ($user->isSuperAdmin()) {
            // pas de restrictions pour les SA
        } elseif ($user->isTerritoryAdmin()) {
            $qb->andWhere('s.territory IN (:territories)')->setParameter('territories', $user->getPartnersTerritories());
        } else {
            // inutilisé pour l'instant car la route est limité au RT, mais fonctionnel pour les autres profils.
            $qb->leftJoin('s.affectations', 'affectations')
                ->leftJoin('affectations.partner', 'partner')
                ->andWhere('partner IN (:partners)')
                ->setParameter('partners', $user->getPartners());
        }

        return $qb->getQuery()->getArrayResult();
    }
}
