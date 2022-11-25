<?php

namespace App\Manager;

use App\Entity\Affectation;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;

class AffectationManager extends Manager
{
    public function __construct(
        private Security $security,
        protected ManagerRegistry $managerRegistry,
        string $entityName = ''
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->entityName = $entityName;
    }

    public function updateAffection(Affectation $affectation, string $status): Affectation
    {
        $affectation->setStatut($status);
        $affectation->setAnsweredAt(new \DateTimeImmutable());
        $affectation->setAnsweredBy($this->security->getUser());

        $this->save($affectation);

        return $affectation;
    }
}
