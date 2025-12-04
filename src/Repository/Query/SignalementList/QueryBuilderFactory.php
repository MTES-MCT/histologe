<?php

namespace App\Repository\Query\SignalementList;

use App\Dto\SignalementAffectationListView;
use App\Entity\Affectation;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\User;
use App\Repository\SignalementRepository;
use App\Service\DashboardTabPanel\TabQueryParameters;
use App\Service\Signalement\SearchFilter;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

readonly class QueryBuilderFactory
{
    public function __construct(
        private EntityManagerInterface $em,
        private SearchFilter $searchFilter,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws Exception
     */
    public function create(User $user, array $options = []): QueryBuilder
    {
        $qb = $this->em->createQueryBuilder()->from(Signalement::class, 's');

        /** @var SignalementRepository $signalementRepository */
        $signalementRepository = $this->em->getRepository(Signalement::class);
        $qb->select('
            DISTINCT s.id,
            s.uuid,
            s.reference,
            s.referenceInjonction,
            s.createdAt,
            s.statut,
            s.score,
            s.isNotOccupant,
            s.nomOccupant,
            s.prenomOccupant,
            s.adresseOccupant,
            s.cpOccupant,
            s.villeOccupant,
            s.lastSuiviAt,
            s.lastSuiviBy,
            s.lastSuiviIsPublic,
            s.profileDeclarant,
            territory.id as territoryId,
            GROUP_CONCAT(DISTINCT CONCAT(p.nom, :concat_separator, a.statut) SEPARATOR :group_concat_separator) as rawAffectations,
            GROUP_CONCAT(DISTINCT p.nom SEPARATOR :group_concat_separator) as affectationPartnerName,
            GROUP_CONCAT(DISTINCT a.statut SEPARATOR :group_concat_separator) as affectationStatus,
            GROUP_CONCAT(DISTINCT sq.qualification SEPARATOR :group_concat_separator) as qualifications,
            GROUP_CONCAT(DISTINCT sq.status SEPARATOR :group_concat_separator) as qualificationsStatuses,
            GROUP_CONCAT(DISTINCT i.concludeProcedure ORDER BY i.scheduledAt DESC SEPARATOR :group_concat_separator) as conclusionsProcedure')
            ->leftJoin('s.affectations', 'a')
            ->leftJoin('a.partner', 'p')
            ->leftJoin('s.signalementQualifications', 'sq', 'WITH', 'sq.status LIKE \'%AVEREE%\' OR sq.status LIKE \'%CHECK%\'')
            ->leftJoin('s.interventions', 'i', 'WITH', 'i.type LIKE \'VISITE\' OR i.type LIKE \'ARRETE_PREFECTORAL\'')
            ->leftJoin('s.territory', 'territory')
            ->where('s.statut NOT IN (:statusList)')
            ->groupBy('s.id')
            ->setParameter('concat_separator', SignalementAffectationListView::SEPARATOR_CONCAT)
            ->setParameter('group_concat_separator', SignalementAffectationListView::SEPARATOR_GROUP_CONCAT);

        if ($user->isTerritoryAdmin()) {
            if (empty($options['territories'])) {
                $qb->andWhere('s.territory IN (:territories)')->setParameter('territories', $user->getPartnersTerritories());
            }
        } elseif ($user->isUserPartner() || $user->isPartnerAdmin()) {
            if (empty($options['territories'])) {
                $qb->andWhere('s.territory IN (:territories)')->setParameter('territories', $user->getPartnersTerritories());
            }
            $statuses = [];
            if (!empty($options['statuses'])) {
                $statuses = array_map(function ($status) {
                    return SignalementStatus::tryFrom($status)?->mapAffectationStatus();
                }, $options['statuses']);
            }

            $subQueryBuilder = $this->em->createQueryBuilder()
                ->select('DISTINCT IDENTITY(a2.signalement)')
                ->from(Affectation::class, 'a2')
                ->where('a2.partner IN (:partners)');

            if (!empty($options['statuses'])) {
                $subQueryBuilder->andWhere('a2.statut IN (:statut_affectation)');
            }
            $qb->andWhere('s.id IN ('.$subQueryBuilder->getDQL().')');

            $qb->setParameter('partners', $user->getPartners());
            if (!empty($options['statuses'])) {
                $qb->setParameter('statut_affectation', $statuses);
            }
        }

        if (!empty($options['bailleurSocial'])) {
            $qb->andWhere('s.bailleur = :bailleur')
                ->setParameter('bailleur', $options['bailleurSocial']);
        }
        $qb->setParameter('statusList', SignalementStatus::excludedStatuses());
        $qb = $this->searchFilter->applyFilters($qb, $options, $user);

        if (!empty($options['relanceUsagerSansReponse'])) {
            $signalementIds = $signalementRepository->getSignalementsIdAvecRelancesSansReponse();
            $qb->andWhere('s.id IN (:signalement_ids)')
                ->setParameter('signalement_ids', $signalementIds);
        }

        if (!empty($options['isDossiersSansActivite'])) {
            $params = new TabQueryParameters();
            $signalementIds = $signalementRepository->getSignalementsIdSansSuiviPartenaireDepuis60Jours($user, $params);
            $qb->andWhere('s.id IN (:signalement_ids)')
                ->setParameter('signalement_ids', $signalementIds);
        }

        if (!empty($options['isEmailAVerifier'])) {
            $signalementIds = $signalementRepository->findIdsNonDeliverableSignalements($user, null);
            $qb->andWhere('s.id IN (:signalement_ids)')
                ->setParameter('signalement_ids', $signalementIds);
        }

        if (!empty($options['isDossiersSansAgent'])) {
            $params = new TabQueryParameters();
            $signalementUuids = $signalementRepository->getSignalementsUuidSansAgent($params);
            $qb->andWhere('s.uuid IN (:signalement_uuids)')
                ->setParameter('signalement_uuids', $signalementUuids);
        }

        if (isset($options['sortBy'])) {
            switch ($options['sortBy']) {
                case 'reference':
                    $qb
                        ->orderBy('CAST(SUBSTRING_INDEX(s.reference, \'-\', 1) AS UNSIGNED)', $options['orderBy'])
                        ->addOrderBy('CAST(SUBSTRING_INDEX(s.reference, \'-\', -1) AS UNSIGNED)', $options['orderBy']);
                    break;
                case 'nomOccupant':
                    $qb->orderBy('s.nomOccupant', $options['orderBy']);
                    break;
                case 'createdAt':
                    $qb->orderBy('s.createdAt', $options['orderBy']);
                    break;
                case 'lastSuiviAt':
                    $qb->orderBy('s.lastSuiviAt', $options['orderBy']);
                    break;
                case 'villeOccupant':
                    $qb->orderBy('s.villeOccupant', $options['orderBy']);
                    break;
                default:
                    $qb->orderBy('s.createdAt', 'DESC');
            }
        } else {
            $qb->orderBy('s.createdAt', 'DESC');
        }

        return $qb;
    }
}
