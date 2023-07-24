<?php

namespace App\Repository;

use App\Entity\SignalementDraft;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SignalementDraft>
 *
 * @method SignalementDraft|null find($id, $lockMode = null, $lockVersion = null)
 * @method SignalementDraft|null findOneBy(array $criteria, array $orderBy = null)
 * @method SignalementDraft[]    findAll()
 * @method SignalementDraft[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SignalementDraftRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SignalementDraft::class);
    }

    public function save(SignalementDraft $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SignalementDraft $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
