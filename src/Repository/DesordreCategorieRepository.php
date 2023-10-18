<?php

namespace App\Repository;

use App\Entity\DesordreCategorie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DesordreCategorie>
 *
 * @method DesordreCategorie|null find($id, $lockMode = null, $lockVersion = null)
 * @method DesordreCategorie|null findOneBy(array $criteria, array $orderBy = null)
 * @method DesordreCategorie[]    findAll()
 * @method DesordreCategorie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DesordreCategorieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DesordreCategorie::class);
    }
}
