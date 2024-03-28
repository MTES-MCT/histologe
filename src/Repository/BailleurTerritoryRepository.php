<?php

namespace App\Repository;

use App\Entity\BailleurTerritory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BailleurTerritory>
 *
 * @method BailleurTerritory|null find($id, $lockMode = null, $lockVersion = null)
 * @method BailleurTerritory|null findOneBy(array $criteria, array $orderBy = null)
 * @method BailleurTerritory[]    findAll()
 * @method BailleurTerritory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BailleurTerritoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BailleurTerritory::class);
    }
}
