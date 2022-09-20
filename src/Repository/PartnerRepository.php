<?php

namespace App\Repository;

use App\Entity\Partner;
use App\Entity\Territory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Partner|null find($id, $lockMode = null, $lockVersion = null)
 * @method Partner|null findOneBy(array $criteria, array $orderBy = null)
 * @method Partner[]    findAll()
 * @method Partner[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PartnerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Partner::class);
    }

    public function findAllOrByInseeIfCommune(string|null $insee, Territory|null $territory)
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.isArchive != 1');
        if ($insee) {
            $qb->andWhere('p.insee LIKE :insee')
                ->setParameter('insee', '%'.$insee.'%');
        }
        if ($territory) {
            $qb->andWhere('p.territory = :territory')->setParameter('territory', $territory);
        }
        $qb
            ->leftJoin('p.affectations', 'affectations')
            ->addSelect('affectations');

        return $qb
            ->getQuery()
            ->getResult();
    }

    public function findAllList(Territory|null $territory)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('PARTIAL p.{id,nom,isCommune}')
            ->where('p.isArchive != 1');
        if ($territory) {
            $qb->andWhere('p.territory = :territory')->setParameter('territory', $territory);
        }

        return $qb->indexBy('p', 'p.id')
            ->getQuery()
            ->getResult();
    }
}
