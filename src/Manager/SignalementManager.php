<?php

namespace App\Manager;

use App\Entity\Affectation;
use App\Entity\Partner;
use App\Entity\Signalement;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;

class SignalementManager extends AbstractManager
{
    public function __construct(protected ManagerRegistry $managerRegistry,
                                private Security $security,
                                string $entityName = Signalement::class
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    public function findAllPartners(Signalement $signalement): array
    {
        $partners['affected'] = $this->managerRegistry->getRepository(Partner::class)->findByLocalization(
            signalement: $signalement,
            affected: true
        );

        $partners['not_affected'] = $this->managerRegistry->getRepository(Partner::class)->findByLocalization(
            signalement: $signalement,
            affected: false
        );

        return $partners;
    }

    public function closeSignalementForAllPartners(Signalement $signalement, string $motif): Signalement
    {
        $signalement->setStatut(Signalement::STATUS_CLOSED)
            ->setMotifCloture($motif)
            ->setClosedAt(new \DateTimeImmutable());

        foreach ($signalement->getAffectations() as $affectation) {
            $affectation
                ->setStatut(Affectation::STATUS_CLOSED)
                ->setMotifCloture($motif)
                ->setAnsweredBy($this->security->getUser());
            $this->managerRegistry->getManager()->persist($affectation);
        }
        $this->managerRegistry->getManager()->flush();
        $this->save($signalement);

        return $signalement;
    }

    public function closeAffectation(Affectation $affectation, string $motif): Affectation
    {
        $affectation
            ->setStatut(Affectation::STATUS_CLOSED)
            ->setAnsweredAt(new \DateTimeImmutable())
            ->setMotifCloture($motif);

        $this->managerRegistry->getManager()->persist($affectation);
        $this->managerRegistry->getManager()->flush($affectation);

        return $affectation;
    }
}
