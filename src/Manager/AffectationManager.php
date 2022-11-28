<?php

namespace App\Manager;

use App\Entity\Affectation;
use App\Entity\Partner;
use App\Entity\Signalement;
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

    public function createAffectationFrom(Signalement $signalement, Partner $partner): Affectation
    {
        return (new Affectation())
            ->setSignalement($signalement)
            ->setPartner($partner)
            ->setAffectedBy($this->security->getUser())
            ->setTerritory($partner->getTerritory());
    }

    public function removeAffectationsBy(Signalement $signalement, array $partnersIdToRemove = []): void
    {
        if (empty($partnersIdToRemove)) {
            $signalement->getAffectations()->filter(function (Affectation $affectation) {
                $this->remove($affectation);
            });
        } else {
            foreach ($partnersIdToRemove as $partnerIdToRemove) {
                $partner = $this->managerRegistry->getRepository(Partner::class)->find($partnerIdToRemove);
                $signalement->getAffectations()->filter(
                    function (Affectation $affectation) use ($partner) {
                        if ($affectation->getPartner()->getId() === $partner->getId()) {
                            $this->remove($affectation);
                        }
                    });
            }
        }
    }
}
