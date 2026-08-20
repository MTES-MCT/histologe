<?php

namespace App\Repository\Query\Commune;

use App\Entity\Commune;
use App\Entity\Epci;
use App\Entity\Territory;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class CommuneEpciQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findEpciByCommuneTerritory(?Territory $territory = null, ?User $user = null): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Commune::class, 'c')
            ->select('distinct e.code, e.nom')
            ->join('c.epci', 'e');
        if ($user && !$user->isSuperAdmin()) {
            $qb->andWhere('c.territory IN (:territories)')->setParameter('territories', $user->getPartnersTerritories());
        }
        if (null !== $territory) {
            $qb
                ->andWhere('c.territory = :territory')
                ->setParameter('territory', $territory);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array<int, string> $epciCodes
     *
     * @return array<int, array<string, mixed>>
     */
    public function findCommunesByEpcis(array $epciCodes): array
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Epci::class, 'e')
            ->select('DISTINCT c.nom, c.codePostal')
            ->innerJoin('e.communes', 'c')
            ->where('e.code IN (:epci_codes)')
            ->setParameter('epci_codes', $epciCodes);

        return $qb->getQuery()->getArrayResult();
    }
}
