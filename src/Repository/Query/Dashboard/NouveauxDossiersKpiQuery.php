<?php

namespace App\Repository\Query\Dashboard;

use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\User;
use App\Entity\UserSignalementSubscription;
use App\Service\DashboardTabPanel\Kpi\CountNouveauxDossiers;
use Doctrine\ORM\EntityManagerInterface;

class NouveauxDossiersKpiQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<int, \App\Entity\Territory> $territories
     */
    public function countNouveauxDossiersKpi(array $territories = [], ?User $user = null): CountNouveauxDossiers
    {
        $select = sprintf(
            'NEW %s(
            %s, -- countFormulaireUsager
            %s, -- countFormulairePro
            %s, -- countSansAffectation
            %s, -- countNouveauxDossiers
            %s  -- countNoAgentDossiers
        )',
            CountNouveauxDossiers::class,
            $user ? 0 : 'COALESCE(SUM(CASE WHEN s.statut = :statut_validation AND s.createdBy IS NULL THEN 1 ELSE 0 END), 0)',
            $user ? 0 : 'COALESCE(SUM(CASE WHEN s.statut = :statut_validation AND s.createdBy IS NOT NULL THEN 1 ELSE 0 END), 0)',
            $user ? 0 : 'COALESCE(SUM(CASE WHEN s.statut = :statut_active AND a.id IS NULL THEN 1 ELSE 0 END), 0)',
            $user ? 'COALESCE(SUM(CASE WHEN a.partner IN (:partners) AND a.statut = :affectation_wait THEN 1 ELSE 0 END), 0)' : 0,
            $user ? 'COALESCE(SUM(CASE WHEN a.partner IN (:partners) AND a.statut = :affectation_accepted AND NOT EXISTS(
                        SELECT 1 FROM '.UserSignalementSubscription::class.' uss
                        WHERE uss.signalement = s
                        AND EXISTS(
                            SELECT 1 FROM '.User::class.' u2
                            JOIN u2.userPartners up2
                            WHERE uss.user = u2
                            AND up2.partner IN (:partners)
                        )
                    ) THEN 1 ELSE 0 END), 0)' : 0,
        );

        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's')
            ->select($select)
            ->leftJoin('s.affectations', 'a');

        if (null === $user) {
            $qb->setParameter('statut_active', SignalementStatus::ACTIVE);
            $qb->setParameter('statut_validation', SignalementStatus::NEED_VALIDATION);
        }

        if (!empty($territories)) {
            $qb->andWhere('s.territory IN (:territories)')
                ->setParameter('territories', $territories);
        }

        if ($user?->isUserPartner() || $user?->isPartnerAdmin()) {
            $qb->setParameter('partners', $user->getPartners())
                ->setParameter('affectation_wait', AffectationStatus::WAIT)
                ->setParameter('affectation_accepted', AffectationStatus::ACCEPTED);
        }

        return $qb->getQuery()->getSingleResult();
    }
}
