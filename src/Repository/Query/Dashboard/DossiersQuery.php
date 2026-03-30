<?php

namespace App\Repository\Query\Dashboard;

use App\Entity\Affectation;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\CreationSource;
use App\Entity\Enum\PartnerType;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Entity\User;
use App\Service\DashboardTabPanel\Kpi\CountAfermer;
use App\Service\DashboardTabPanel\TabDossier;
use App\Service\DashboardTabPanel\TabQueryParameters;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

class DossiersQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        private readonly DossiersAvecRelanceSansReponseQuery $dossiersAvecRelanceSansReponseQuery,
    ) {
    }

    private function getBaseQueryBuilder(
        User $user,
        ?SignalementStatus $signalementStatus = null,
        ?AffectationStatus $affectationStatus = null,
        ?bool $onlyWithoutSubscription = false,
        ?TabQueryParameters $tabQueryParameters = null,
    ): QueryBuilder {
        $qb = $this->entityManager->createQueryBuilder()
            ->from(Signalement::class, 's');

        if (null !== $signalementStatus) {
            $qb
                ->andWhere('s.statut = :statut')
                ->setParameter('statut', $signalementStatus);
        }

        if ($tabQueryParameters?->territoireId) {
            $qb
                ->andWhere('s.territory = :territoireId')
                ->setParameter('territoireId', $tabQueryParameters->territoireId);
        } elseif (!$user->isSuperAdmin()) {
            $qb->andWhere('s.territory IN (:territories)')->setParameter('territories', $user->getPartnersTerritories());
        }

        if ($tabQueryParameters->createdFrom) {
            if (CreationSource::CREATED_FROM_FORMULAIRE_USAGER === $tabQueryParameters->createdFrom) {
                $qb->andWhere('s.creationSource IN (:creationSources)')
                    ->setParameter('creationSources', CreationSource::getFormUsagerValues());
            } elseif (CreationSource::CREATED_FROM_FORMULAIRE_PRO === $tabQueryParameters->createdFrom) {
                $qb->andWhere('s.creationSource IN (:creationSources)')
                    ->setParameter('creationSources', CreationSource::getFormProValues());
            }
        }

        if (!empty($tabQueryParameters->partenairesId)) {
            if (\in_array('AUCUN', $tabQueryParameters->partenairesId)) {
                $qb->leftJoin('s.affectations', 'a')->andWhere('a.partner IS NULL');
            } else {
                $qb
                    ->leftJoin('s.affectations', 'a')
                    ->andWhere('a.partner IN (:partenairesId)')
                    ->setParameter('partenairesId', $tabQueryParameters->partenairesId);
            }
        }

        if ($affectationStatus) {
            $qb->andWhere('a.statut = :affectationStatus');
            $qb->setParameter('affectationStatus', $affectationStatus);
        }

        if ($onlyWithoutSubscription) {
            $subquery = 'SELECT u FROM '.User::class.' u JOIN u.userPartners up JOIN up.partner p WHERE p IN (:partners)';
            $qb
                ->leftJoin('s.userSignalementSubscriptions', 'uss', 'WITH', 'uss.user IN ('.$subquery.')')
                ->andWhere('uss.id IS NULL')
                ->setParameter('partners', $user->getPartners());
        }

        return $qb;
    }

    /**
     * @return TabDossier[]
     */
    public function findNewDossiersFrom(
        ?SignalementStatus $signalementStatus = null,
        ?AffectationStatus $affectationStatus = null,
        ?TabQueryParameters $tabQueryParameters = null,
    ): array {
        /** @var User $user */
        $user = $this->security->getUser();

        $qb = $this->getBaseQueryBuilder(
            user: $user,
            signalementStatus: $signalementStatus,
            affectationStatus: $affectationStatus,
            tabQueryParameters: $tabQueryParameters
        );

        if (CreationSource::CREATED_FROM_FORMULAIRE_PRO === $tabQueryParameters->createdFrom) {
            $qb
                ->leftJoin('s.createdBy', 'u')
                ->leftJoin('u.userPartners', 'up')
                ->leftJoin('up.partner', 'p');
        }

        $qb->select(
            \sprintf(
                'NEW %s(
                    s.uuid,
                    s.profileDeclarant,
                    s.nomOccupant,
                    s.prenomOccupant,
                    s.reference,
                    CONCAT_WS(\', \', s.adresseOccupant, CONCAT(s.cpOccupant, \' \', s.villeOccupant)),
                    s.createdAt,'.
                    (CreationSource::CREATED_FROM_FORMULAIRE_PRO === $tabQueryParameters->createdFrom
                        ? 'CONCAT(UPPER(u.nom), \' \', u.prenom), p.nom,'
                        : '\'\' , \'\' ,'
                    ).
                    'CASE
                        WHEN s.isLogementSocial = true THEN \'PUBLIC\'
                        ELSE \'PRIVÉ\'
                    END,
                    s.validatedAt
                )',
                TabDossier::class
            )
        );

        if (null !== $tabQueryParameters
            && in_array($tabQueryParameters->sortBy, ['createdAt', 'nomOccupant'], true)
            && in_array($tabQueryParameters->orderBy, ['ASC', 'DESC', 'asc', 'desc'], true)
        ) {
            $qb->orderBy('s.'.$tabQueryParameters->sortBy, $tabQueryParameters->orderBy);
        } else {
            $qb->orderBy('s.createdAt', 'DESC');
        }

        $qb->setMaxResults(TabDossier::MAX_ITEMS_LIST);

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countNewDossiersFrom(
        ?SignalementStatus $signalementStatus = null,
        ?AffectationStatus $affectationStatus = null,
        ?TabQueryParameters $tabQueryParameters = null,
    ): int {
        /** @var User $user */
        $user = $this->security->getUser();

        $qb = $this->getBaseQueryBuilder(
            user: $user,
            signalementStatus: $signalementStatus,
            affectationStatus: $affectationStatus,
            tabQueryParameters: $tabQueryParameters
        );

        $qb->select('COUNT(s.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return TabDossier[]
     */
    public function findDossiersNoAgentFrom(
        ?AffectationStatus $affectationStatus = null,
        ?TabQueryParameters $tabQueryParameters = null,
        bool $isLimitApplied = true,
    ): array {
        /** @var User $user */
        $user = $this->security->getUser();

        $qb = $this->getBaseQueryBuilder(
            user: $user,
            affectationStatus: $affectationStatus,
            onlyWithoutSubscription: true,
            tabQueryParameters: $tabQueryParameters
        );

        $qb->select(
            \sprintf(
                'NEW %s(
                    s.uuid,
                    s.profileDeclarant,
                    s.nomOccupant,
                    s.prenomOccupant,
                    s.reference,
                    CONCAT_WS(\', \', s.adresseOccupant, CONCAT(s.cpOccupant, \' \', s.villeOccupant)),
                    s.createdAt,'.
                    '\'\' , \'\' ,
                    CASE
                        WHEN s.isLogementSocial = true THEN \'PUBLIC\'
                        ELSE \'PRIVÉ\'
                    END,
                    s.validatedAt
                )',
                TabDossier::class
            )
        );

        if (null !== $tabQueryParameters
            && in_array($tabQueryParameters->sortBy, ['createdAt', 'nomOccupant'], true)
            && in_array($tabQueryParameters->orderBy, ['ASC', 'DESC', 'asc', 'desc'], true)
        ) {
            $qb->orderBy('s.'.$tabQueryParameters->sortBy, $tabQueryParameters->orderBy);
        } else {
            $qb->orderBy('s.createdAt', 'DESC');
        }

        if ($isLimitApplied) {
            $qb->setMaxResults(TabDossier::MAX_ITEMS_LIST);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countDossiersNoAgentFrom(
        ?AffectationStatus $affectationStatus = null,
        ?TabQueryParameters $tabQueryParameters = null,
    ): int {
        /** @var User $user */
        $user = $this->security->getUser();

        $qb = $this->getBaseQueryBuilder(
            user: $user,
            affectationStatus: $affectationStatus,
            onlyWithoutSubscription: true,
            tabQueryParameters: $tabQueryParameters
        );

        $qb->select('COUNT(s.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return string[]
     */
    public function getSignalementsUuidSansAgent(TabQueryParameters $params): array
    {
        /** @var User $user */
        $user = $this->security->getUser();
        if (null === $params->territoireId) {
            $params->partenairesId = $user->getPartners()
                ->map(static fn ($partner) => $partner->getId())
                ->toArray();
        }

        $signalements = $this->findDossiersNoAgentFrom(AffectationStatus::ACCEPTED, $params, false);

        return array_map(static fn (TabDossier $dossier) => $dossier->uuid, $signalements);
    }

    /**
     * @return TabDossier[]
     *
     * @throws \DateMalformedStringException
     */
    public function findDossiersFermePartenaireTous(?TabQueryParameters $tabQueryParameters): array
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $qb = $this->getBaseQueryBuilder(
            user: $user,
            signalementStatus: SignalementStatus::ACTIVE,
            tabQueryParameters: $tabQueryParameters
        );

        $qb->select("
            s.uuid,
            s.nomOccupant,
            s.prenomOccupant,
            s.reference,
            CONCAT_WS(', ', s.adresseOccupant, CONCAT(s.cpOccupant, ' ', s.villeOccupant)) AS fullAddress,
            MAX(a.answeredAt) AS lastClosedAt
        ")
            ->innerJoin('s.affectations', 'a')
            ->groupBy('s.uuid, s.nomOccupant, s.prenomOccupant, s.reference, s.adresseOccupant, s.cpOccupant, s.villeOccupant')
            ->having('COUNT(a.id) = SUM(CASE WHEN a.statut = :closed THEN 1 ELSE 0 END)')
            ->setParameter('closed', AffectationStatus::CLOSED);

        if (null !== $tabQueryParameters
            && 'closedAt' === $tabQueryParameters->sortBy
            && in_array($tabQueryParameters->orderBy, ['ASC', 'DESC', 'asc', 'desc'], true)
        ) {
            $qb->orderBy('MAX(a.answeredAt)', $tabQueryParameters->orderBy);
        } else {
            $qb->orderBy('MAX(a.answeredAt)', 'ASC');
        }

        $qb->setMaxResults(TabDossier::MAX_ITEMS_LIST);

        $rows = $qb->getQuery()->getArrayResult();

        return array_map(
            static fn (array $row) => new TabDossier(
                uuid: $row['uuid'],
                nomOccupant: $row['nomOccupant'],
                prenomOccupant: $row['prenomOccupant'],
                reference: $row['reference'],
                adresse: $row['fullAddress'],
                clotureAt: $row['lastClosedAt'] ? new \DateTimeImmutable($row['lastClosedAt']) : null,
            ),
            $rows
        );
    }

    public function countDossiersFermePartenaireTous(?TabQueryParameters $tabQueryParameters): int
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $qb = $this->getBaseQueryBuilder(
            user: $user,
            signalementStatus: SignalementStatus::ACTIVE,
            tabQueryParameters: $tabQueryParameters
        );

        $existsAtLeastOneAffectation = $this->entityManager->createQueryBuilder()
            ->select('1')
            ->from(Affectation::class, 'a1')
            ->where('a1.signalement = s')
            ->getDQL();

        $existsAffectationNotClosed = $this->entityManager->createQueryBuilder()
            ->select('1')
            ->from(Affectation::class, 'a2')
            ->where('a2.signalement = s')
            ->andWhere('a2.statut != :closed')
            ->getDQL();

        $qb->andWhere($qb->expr()->exists($existsAtLeastOneAffectation));
        $qb->andWhere($qb->expr()->not($qb->expr()->exists($existsAffectationNotClosed)));

        $qb->select('COUNT(DISTINCT s.id)');
        $qb->setParameter('closed', AffectationStatus::CLOSED);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return TabDossier[]
     *
     * @throws \DateMalformedStringException
     */
    public function findDossiersFermePartenaireCommune(?TabQueryParameters $tabQueryParameters): array
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $qb = $this->getBaseQueryBuilder(
            user: $user,
            signalementStatus: SignalementStatus::ACTIVE,
            tabQueryParameters: $tabQueryParameters
        );

        $qb->select("
            s.uuid,
            s.nomOccupant,
            s.prenomOccupant,
            s.reference,
            CONCAT_WS(', ', s.adresseOccupant, CONCAT(s.cpOccupant, ' ', s.villeOccupant)) AS fullAddress,
            MAX(a.answeredAt) AS lastClosedAt
        ")
            ->innerJoin('s.affectations', 'a')
            ->innerJoin('a.partner', 'p')
            ->andWhere('p.type = :partnerType')
            ->andWhere('a.statut = :closed')
            ->groupBy('s.uuid, s.nomOccupant, s.prenomOccupant, s.reference, s.adresseOccupant, s.cpOccupant, s.villeOccupant')
            ->setParameter('closed', AffectationStatus::CLOSED)
            ->setParameter('partnerType', PartnerType::COMMUNE_SCHS);

        if (null !== $tabQueryParameters
            && 'closedAt' === $tabQueryParameters->sortBy
            && in_array($tabQueryParameters->orderBy, ['ASC', 'DESC', 'asc', 'desc'], true)
        ) {
            $qb->orderBy('MAX(a.answeredAt)', $tabQueryParameters->orderBy);
        } else {
            $qb->orderBy('MAX(a.answeredAt)', 'ASC');
        }

        $qb->setMaxResults(TabDossier::MAX_ITEMS_LIST);

        $rows = $qb->getQuery()->getArrayResult();

        return array_map(
            static fn (array $row) => new TabDossier(
                uuid: $row['uuid'],
                nomOccupant: $row['nomOccupant'],
                prenomOccupant: $row['prenomOccupant'],
                reference: $row['reference'],
                adresse: $row['fullAddress'],
                clotureAt: $row['lastClosedAt'] ? new \DateTimeImmutable($row['lastClosedAt']) : null,
            ),
            $rows
        );
    }

    public function countDossiersFermePartenaireCommune(?TabQueryParameters $tabQueryParameters): int
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $qb = $this->getBaseQueryBuilder(
            user: $user,
            signalementStatus: SignalementStatus::ACTIVE,
            tabQueryParameters: $tabQueryParameters
        );

        $qb->select('COUNT(DISTINCT s.id)')
            ->innerJoin('s.affectations', 'a')
            ->innerJoin('a.partner', 'p')
            ->andWhere('p.type = :partnerType')
            ->andWhere('a.statut = :closed')
            ->setParameter('partnerType', PartnerType::COMMUNE_SCHS)
            ->setParameter('closed', AffectationStatus::CLOSED);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return TabDossier[]
     *
     * @throws \DateMalformedStringException
     */
    public function findDossiersDemandesFermetureByUsager(?TabQueryParameters $tabQueryParameters): array
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $qb = $this->getBaseQueryBuilder(
            user: $user,
            signalementStatus: SignalementStatus::ACTIVE,
            tabQueryParameters: $tabQueryParameters
        );

        $qb->select("
            s.uuid,
            s.nomOccupant,
            s.prenomOccupant,
            s.reference,
            CONCAT_WS(', ', s.adresseOccupant, CONCAT(s.cpOccupant, ' ', s.villeOccupant)) AS fullAddress,
            MAX(su.createdAt) AS demandeFermetureUsagerAt,
            DATEDIFF(CURRENT_DATE(), MAX(su.createdAt)) AS demandeFermetureUsagerDaysAgo,
            CASE WHEN s.isNotOccupant = 1 THEN 'TIERS DÉCLARANT' ELSE 'OCCUPANT' END AS demandeFermetureUsagerProfileDeclarant
        ")
            ->innerJoin('s.suivis', 'su', 'WITH', 'su.category = :suivi_category_abandon_procedure')
            ->andWhere('s.isUsagerAbandonProcedure = 1')
            ->groupBy('s.uuid, s.nomOccupant, s.prenomOccupant, s.reference, s.adresseOccupant, s.cpOccupant, s.villeOccupant, s.isNotOccupant')
            ->orderBy('MAX(su.createdAt)', 'DESC')
            ->setParameter('suivi_category_abandon_procedure', SuiviCategory::DEMANDE_ABANDON_PROCEDURE);

        if (null !== $tabQueryParameters
            && 'demandeFermetureUsagerAt' === $tabQueryParameters->sortBy
            && in_array($tabQueryParameters->orderBy, ['ASC', 'DESC', 'asc', 'desc'], true)
        ) {
            $qb->orderBy('MAX(su.createdAt)', $tabQueryParameters->orderBy);
        } else {
            $qb->orderBy('MAX(su.createdAt)', 'ASC');
        }

        $qb->setMaxResults($tabQueryParameters?->limit ?? TabDossier::MAX_ITEMS_LIST);

        $rows = $qb->getQuery()->getArrayResult();

        return array_map(
            static fn (array $row) => new TabDossier(
                uuid: $row['uuid'],
                nomOccupant: $row['nomOccupant'] ?? null,
                prenomOccupant: $row['prenomOccupant'] ?? null,
                reference: $row['reference'] ?? null,
                adresse: $row['fullAddress'] ?? null,
                demandeFermetureUsagerDaysAgo: isset($row['demandeFermetureUsagerDaysAgo']) ? (int) $row['demandeFermetureUsagerDaysAgo'] : null,
                demandeFermetureUsagerProfileDeclarant: $row['demandeFermetureUsagerProfileDeclarant'] ?? null,
                demandeFermetureUsagerAt: null !== $row['demandeFermetureUsagerAt'] ? new \DateTimeImmutable($row['demandeFermetureUsagerAt']) : null
            ),
            $rows
        );
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countDossiersDemandesFermetureByUsager(?TabQueryParameters $tabQueryParameters): int
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $qb = $this->getBaseQueryBuilder(
            user: $user,
            signalementStatus: SignalementStatus::ACTIVE,
            tabQueryParameters: $tabQueryParameters
        );

        $qb
            ->select('COUNT(DISTINCT s.uuid)')
            ->innerJoin('s.suivis', 'su', 'WITH', 'su.category = :suivi_category_abandon_procedure')
            ->andWhere('s.isUsagerAbandonProcedure = 1')
            ->setParameter('suivi_category_abandon_procedure', SuiviCategory::DEMANDE_ABANDON_PROCEDURE);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function countAllDossiersAferme(User $user, ?TabQueryParameters $params): CountAfermer
    {
        return new CountAfermer(
            countDemandesFermetureByUsager: $this->countDossiersDemandesFermetureByUsager($params),
            countDossiersRelanceSansReponse: $this->dossiersAvecRelanceSansReponseQuery->countSignalements($params),
            countDossiersFermePartenaireTous: $this->countDossiersFermePartenaireTous($params),
            countDossiersFermeCommune: $this->countDossiersFermePartenaireCommune($params),
        );
    }
}
