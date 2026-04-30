<?php

namespace App\Manager;

use App\Entity\Signalement;
use App\Entity\SignalementUsager;
use App\Entity\User;
use App\Repository\SignalementUsagerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class SignalementUsagerManager extends Manager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SignalementUsagerRepository $signalementUsagerRepository,
        protected ManagerRegistry $managerRegistry,
        string $entityName = SignalementUsager::class,
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    public function createOrUpdate(Signalement $signalement, ?User $userOccupant, ?User $userDeclarant): SignalementUsager
    {
        /** @var SignalementUsager|null $signalementUsager */
        $signalementUsager = $this->signalementUsagerRepository->findOneBy([
            'signalement' => $signalement,
        ]);
        if (null === $signalementUsager) {
            $signalementUsager = (new SignalementUsager())
                ->setSignalement($signalement);
        }
        if (null !== $userOccupant) {
            $signalementUsager->setOccupant($userOccupant);
        }
        if (null !== $userDeclarant) {
            $signalementUsager->setDeclarant($userDeclarant);
        }
        $this->entityManager->persist($signalementUsager);
        $this->entityManager->flush();

        return $signalementUsager;
    }
}
