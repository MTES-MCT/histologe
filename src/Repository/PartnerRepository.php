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
        /*
         * @todo: necessary to clean data and add some constraint in partner creation to know
         * if partner should have `is_commune` 0 or 1 depends if code insee exists
         */
        if ($insee) {
            $qb->andWhere('p.isCommune = 0 OR p.isCommune = 1 AND p.insee LIKE :insee')
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
