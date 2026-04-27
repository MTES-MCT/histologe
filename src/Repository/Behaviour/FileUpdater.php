<?php

namespace App\Repository\Behaviour;

use App\Entity\File;
use App\Entity\Signalement;
use Doctrine\ORM\EntityManagerInterface;

class FileUpdater
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param array<int, int> $ids
     */
    public function updateWithOriginalAndVariants(array $ids): void
    {
        if (!empty($ids)) {
            // Étape 2 : Mettre à jour les enregistrements sélectionnés
            $this->entityManager->createQueryBuilder()->from(File::class, 'f')
                ->update()
                ->set('f.isOriginalDeleted', true)
                ->where('f.id IN (:ids)')
                ->setParameter('ids', $ids)
                ->getQuery()
                ->execute();
        }
    }

    public function updateIsWaitingSuiviForSignalement(Signalement $signalement): void
    {
        $this->entityManager->createQueryBuilder()->from(File::class, 'f')
            ->update()
            ->set('f.isWaitingSuivi', ':isWaitingSuivi')
            ->where('f.signalement = :signalement')
            ->setParameter('isWaitingSuivi', false)
            ->setParameter('signalement', $signalement)
            ->getQuery()
            ->execute();
    }
}
