<?php

namespace App\Manager;

use App\Entity\Affectation;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Territory;
use App\Factory\SignalementFactory;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;

class SignalementManager extends AbstractManager
{
    public function __construct(protected ManagerRegistry $managerRegistry,
                                private Security $security,
                                private SignalementFactory $signalementFactory,
                                string $entityName = Signalement::class
    ) {
        parent::__construct($managerRegistry, $entityName);
    }

    public function createOrGet(Territory $territory, array $data, bool $isImported = false): ?Signalement
    {
        /** @var Signalement|null $signalement */
        $signalement = $this->getRepository()->findOneBy([
            'territory' => $territory,
            'reference' => $data['reference'],
        ]);

        if ($signalement instanceof Signalement) {
            return $signalement;
        }

        return $this->signalementFactory->createInstanceFrom($territory, $data, $isImported);
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

    public function findPartners(Signalement $signalement): array
    {
        $affectation = $signalement->getAffectations()->map(
            function (Affectation $affectation) {
                return $affectation->getPartner()->getId();
            });

        return $affectation->toArray();
    }

    public function closeSignalementForAllPartners(Signalement $signalement, string $motif): Signalement
    {
        $signalement
            ->setStatut(Signalement::STATUS_CLOSED)
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
        $this->managerRegistry->getManager()->flush();

        return $affectation;
    }

    public function findEmailsAffectedToSignalement(Signalement $signalement): array
    {
        $sendTo = [];

        $usersPartnerEmail = $this->getRepository()->findUsersPartnerEmailAffectedToSignalement(
            $signalement->getId(),
        );
        $sendTo = array_merge($sendTo, $usersPartnerEmail);

        $partnersEmail = $this->getRepository()->findPartnersEmailAffectedToSignalement(
            $signalement->getId()
        );

        return array_merge($sendTo, $partnersEmail);
    }
}
