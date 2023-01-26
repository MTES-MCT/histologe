<?php

namespace App\Manager;

use App\Entity\Signalement;
use App\Entity\SignalementUsager;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

class SignalementUsagerManager extends AbstractManager
{
    public function __construct(protected ManagerRegistry $managerRegistry, string $entityName = SignalementUsager::class)
    {
        parent::__construct($managerRegistry, $entityName);
    }

    public function createOrUpdate(Signalement $signalement, ?User $userOccupant, ?User $userDeclarant): SignalementUsager
    {
        /** @var SignalementUsager|null $signalementUsager */
        $signalementUsager = $this->getRepository()->findOneBy([
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
        $this->save($signalementUsager);

        return $signalementUsager;
    }
}
