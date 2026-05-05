<?php

namespace App\Repository;

use App\Dto\Api\Request\SignalementListQueryParams;
use App\Entity\Commune;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\Territory;
use App\Entity\User;
use App\Service\InjonctionBailleur\InjonctionBailleurService;
use App\Service\Interconnection\Idoss\IdossService;
use App\Service\ListFilters\SearchArchivedSignalement;
use App\Service\ListFilters\SearchDraft;
use App\Service\ListFilters\SearchSignalementInjonction;
use App\Service\Security\PartnerAuthorizedResolver;
use App\Utils\Address\CommuneHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\TransactionRequiredException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Signalement>
 *
 * @method Signalement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Signalement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Signalement[]    findAll()
 * @method Signalement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SignalementRepository extends ServiceEntityRepository
{
    private const string DATE_FEEDBACK_USAGER_ONLINE = '2023-03-28';

    public function __construct(
        ManagerRegistry $registry,
        private readonly PartnerAuthorizedResolver $partnerAuthorizedResolver,
    ) {
        parent::__construct($registry, Signalement::class);
    }

    /**
     * @return array<int, array<string, mixed>>|int|string
     */
    public function findCities(User $user, ?Territory $territory = null): array|int|string
    {
        return $this->findCommunes($user, $territory, 's.villeOccupant', 'city');
    }

    /**
     * @return array<int, array<string, mixed>>|int|string
     */
    public function findZipcodes(User $user, ?Territory $territory = null): array|int|string
    {
        return $this->findCommunes($user, $territory, 's.cpOccupant', 'zipcode');
    }

    /**
     * @return array<int, array<string, mixed>>|int|string
     */
    public function findCommunes(
        User $user,
        ?Territory $territory = null,
        ?string $field = null,
        ?string $alias = null,
    ): array|int|string {
        $qb = $this->createQueryBuilder('s')
            ->select($field.' '.$alias)
            ->where('s.statut NOT IN (:statutList)')
            ->setParameter('statutList', SignalementStatus::excludedStatuses());
        if (!$user->isSuperAdmin() && !$user->isTerritoryAdmin()) {
            $qb->leftJoin('s.affectations', 'affectations')
                ->leftJoin('affectations.partner', 'partner')
                ->andWhere('partner IN (:partners)')
                ->setParameter('partners', $user->getPartners());
        }
        if ($territory) {
            $qb->andWhere('s.territory = :territory')
                ->setParameter('territory', $territory);
        } elseif (!$user->isSuperAdmin()) {
            $qb->andWhere('s.territory IN (:territories)')
                ->setParameter('territories', $user->getPartnersTerritories());
        }

        return $qb
            ->groupBy($field)
            ->orderBy($field, 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByCodeForPublic(string $code): ?Signalement
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.codeSuivi = :code')
            ->setParameter('code', $code)
            ->leftJoin('s.suivis', 'suivis', Join::WITH, 'suivis.isVisibleForUsager = 1')
            ->addSelect('suivis')
            ->andWhere('s.statut NOT IN (:statutDraft)')
            ->setParameter('statutDraft', [SignalementStatus::DRAFT, SignalementStatus::DRAFT_ARCHIVED]);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return array<string, string>|null
     *
     * @throws TransactionRequiredException
     * @throws NonUniqueResultException
     */
    public function findLastReferenceByTerritory(Territory $territory): ?array
    {
        $year = (new \DateTime())->format('Y');
        $queryBuilder = $this->createQueryBuilder('s')
            ->select('s.reference')
            ->addSelect("SUBSTRING_INDEX(s.reference, '-', 1) AS year")
            ->addSelect("CAST(SUBSTRING_INDEX(s.reference, '-', -1) AS SIGNED) AS reference_index")
            ->where('YEAR(s.createdAt) = :year')
            ->setParameter('year', $year)
            ->andWhere('s.territory = :territory')
            ->setParameter('territory', $territory)
            ->orderBy('reference_index', 'DESC')
            ->setMaxResults(1);

        return $queryBuilder
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }

    public function findMaxReferenceInjonction(): ?int
    {
        $qb = $this->createQueryBuilder('s')->select('MAX(s.referenceInjonction)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByReferenceChunk(Territory $territory, string $chunkReference): ?Signalement
    {
        return $this->createQueryBuilder('s')
            ->where('s.territory = :territory')
            ->andWhere('s.reference LIKE :reference')
            ->setParameter('territory', $territory)
            ->setParameter('reference', '%'.$chunkReference.'%')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param array<int, int|string> $ids
     *
     * @return array<int, Signalement>
     */
    public function findAllByIds(array $ids): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Signalement>
     */
    public function findWithNoGeolocalisation(?Territory $territory = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.inseeOccupant LIKE :insee_occupant OR s.inseeOccupant IS NULL')
            ->setParameter('insee_occupant', '%#ERROR%');

        if ($territory) {
            $qb->andWhere('s.territory = :territory')
                ->setParameter('territory', $territory)
                ->setParameter('territory', $territory);
        }

        return $qb->getQuery()->getResult();
    }

    public function findOneForEmailAndAddress(
        string $email,
        string $address,
        string $zipcode,
        string $city,
    ): ?Signalement {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.mailDeclarant = :email OR s.mailOccupant = :email')->setParameter('email', $email)
            ->andWhere('s.adresseOccupant = :address')->setParameter('address', $address)
            ->andWhere('s.cpOccupant = :zipcode')->setParameter('zipcode', $zipcode)
            ->andWhere('s.villeOccupant = :city')->setParameter('city', $city)
            ->andWhere('s.statut NOT IN (:statutList)')->setParameter('statutList', SignalementStatus::excludedStatuses());

        $list = $qb->addOrderBy('s.createdAt', 'DESC')
            ->getQuery()->getResult();
        $statutsList = [
            SignalementStatus::ACTIVE,
            SignalementStatus::NEED_VALIDATION,
            SignalementStatus::CLOSED,
            SignalementStatus::REFUSED,
        ];
        foreach ($statutsList as $statut) {
            foreach ($list as $item) {
                if ($item->getStatut() === $statut) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, Signalement>
     */
    public function findAllForEmailAndAddress(
        ?string $email,
        ?string $address,
        ?string $zipcode,
        ?string $city,
        bool $isTiersDeclarant = true,
        ?string $nomOccupant = null,
    ): array {
        if (empty($email) || empty($address) || empty($zipcode) || empty($city)) {
            return [];
        }
        if ($isTiersDeclarant && empty($nomOccupant)) {
            return [];
        }

        $city = CommuneHelper::getCommuneFromArrondissement($city);

        $qb = $this->createQueryBuilder('s');
        if ($isTiersDeclarant) {
            $qb
                ->andWhere('s.mailDeclarant = :email')->setParameter('email', $email)
                ->andWhere('s.nomOccupant = :nomOccupant')->setParameter('nomOccupant', $nomOccupant);
        } else {
            $qb->andWhere('s.mailOccupant = :email')->setParameter('email', $email);
        }
        $qb->andWhere('LOWER(s.adresseOccupant) = :address')->setParameter('address', strtolower($address))
            ->andWhere('s.cpOccupant = :zipcode')->setParameter('zipcode', $zipcode)
            ->andWhere('LOWER(s.villeOccupant) = :city')->setParameter('city', strtolower($city))
            ->andWhere('s.statut IN (:statusSignalement)')
            ->setParameter(
                'statusSignalement',
                [
                    SignalementStatus::ACTIVE,
                    SignalementStatus::NEED_VALIDATION,
                    SignalementStatus::INJONCTION_BAILLEUR,
                ]
            );

        if ($isTiersDeclarant) {
            $qb->addOrderBy('s.createdAt', 'DESC');
        } else {
            $qb->addOrderBy('s.lastSuiviAt', 'DESC');
            $qb->setMaxResults(1);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<int, Signalement>
     */
    public function findSignalementsWithFilesToUploadOnIdoss(Partner $partner): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s', 'f')
            ->innerJoin('s.files', 'f')
            ->innerJoin('s.affectations', 'a')
            ->where("f.synchroData IS NULL OR (JSON_CONTAINS_PATH(f.synchroData, 'one', '$.".IdossService::TYPE_SERVICE."') = 0)")
            ->andWhere("JSON_CONTAINS_PATH(s.synchroData, 'one', '$.".IdossService::TYPE_SERVICE."') = 1")
            ->andWhere('a.partner = :partner')
            ->setParameter('partner', $partner);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Paginator<array<string, mixed>>
     */
    public function findFilteredPaginatedDrafts(
        SearchDraft $searchDraft,
        int $maxResult,
    ): Paginator {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder
            ->where('s.statut IN (:status_list)')
            ->andWhere('s.createdBy = :user')
            ->setParameter('status_list', [SignalementStatus::DRAFT, SignalementStatus::NEED_VALIDATION])
            ->setParameter('user', $searchDraft->getUser());

        if (!empty($searchDraft->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchDraft->getOrderType());
            $queryBuilder->orderBy($orderField, $orderDirection);
        } else {
            $queryBuilder->orderBy('s.createdAt', 'DESC');
        }

        $firstResult = ($searchDraft->getPage() - 1) * $maxResult;
        $queryBuilder->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($queryBuilder->getQuery(), false);
    }

    /**
     * @return Paginator<array<string, mixed>>
     */
    public function findFilteredArchivedPaginated(
        SearchArchivedSignalement $searchArchivedSignalement,
        int $maxResult,
    ): Paginator {
        return $this->findAllArchived(
            page: $searchArchivedSignalement->getPage(),
            maxResult: $maxResult,
            territory: $searchArchivedSignalement->getTerritory(),
            referenceTerms: $searchArchivedSignalement->getQueryReference(),
            searchArchivedSignalement: $searchArchivedSignalement,
        );
    }

    /**
     * @return Paginator<array<string, mixed>>
     */
    public function findAllArchived(
        int $page,
        int $maxResult,
        ?Territory $territory,
        ?string $referenceTerms,
        ?SearchArchivedSignalement $searchArchivedSignalement = null,
    ): Paginator {
        $queryBuilder = $this->createQueryBuilder('s');

        $queryBuilder
            ->where('s.statut = :archived')
            ->setParameter('archived', SignalementStatus::ARCHIVED);

        if (!empty($territory)) {
            $queryBuilder
                ->andWhere('s.territory = :territory')
                ->setParameter('territory', $territory);
        }

        if (!empty($referenceTerms)) {
            $queryBuilder
                ->andWhere('s.reference LIKE :referenceTerms')
                ->setParameter('referenceTerms', $referenceTerms);
        }

        if (!empty($searchArchivedSignalement) && !empty($searchArchivedSignalement->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchArchivedSignalement->getOrderType());
            $queryBuilder->orderBy($orderField, $orderDirection);
        } else {
            $queryBuilder->orderBy('s.createdAt', 'ASC');
        }

        $firstResult = ($page - 1) * $maxResult;
        $queryBuilder->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($queryBuilder->getQuery(), false);
    }

    /**
     * @return array<int, Signalement>
     */
    public function findSignalementsBetweenDates(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        $qb = $this->createQueryBuilder('s');

        return $qb
            ->where('s.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('s.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, Signalement>
     */
    public function findSignalementsByYear(?int $year, Territory $territory, ?bool $emptyGeolocOnly = false): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.territory = :territory')
            ->setParameter('territory', $territory)
            ->orderBy('s.createdAt', 'ASC');

        if ($emptyGeolocOnly) {
            $qb->andWhere('s.geoloc IS NULL OR JSON_LENGTH(s.geoloc) = 0');
        }

        if (null !== $year) {
            $start = new \DateTimeImmutable(sprintf('%d-01-01 00:00:00', $year));
            $end = $start->modify('+1 year');

            $qb
                ->andWhere('s.createdAt >= :start')
                ->andWhere('s.createdAt < :end')
                ->setParameter('start', $start)
                ->setParameter('end', $end);
        }

        return $qb->getQuery()->getResult();
    }

    public function findOneForApi(
        User $user,
        ?string $uuid = null,
        ?string $reference = null,
    ): ?Signalement {
        $qb = $this->findForAPIQueryBuilder($user, true);
        if ($uuid) {
            $qb->andWhere('s.uuid = :uuid')->setParameter('uuid', $uuid);
        }
        if ($reference) {
            $qb->andWhere('s.reference = :reference')->setParameter('reference', $reference);
        }

        if (count($result = $qb->getQuery()->getResult()) > 0) {
            return current($result);
        }

        return null;
    }

    /**
     * @return array<int, Signalement>
     *
     * @throws \DateMalformedStringException
     */
    public function findAllForApi(User $user, SignalementListQueryParams $signalementListQueryParams): array
    {
        $page = (int) ($signalementListQueryParams->page ?? SignalementListQueryParams::DEFAULT_PAGE);
        $limit = (int) ($signalementListQueryParams->limit ?? SignalementListQueryParams::DEFAULT_LIMIT);

        $offset = ($page - 1) * $limit;
        $qb = $this->findForAPIQueryBuilder($user);

        if (!empty($signalementListQueryParams->dateAffectationDebut)) {
            $qb->andWhere('affectations.createdAt >= :dateAffectationStart')
                ->setParameter('dateAffectationStart', $signalementListQueryParams->dateAffectationDebut);
        }

        if (!empty($signalementListQueryParams->dateAffectationFin)) {
            $dateAffectationEnd = (new \DateTimeImmutable($signalementListQueryParams->dateAffectationFin))
                ->modify('+1 day');

            $qb->andWhere('affectations.createdAt <= :dateAffectationEnd')
                ->setParameter('dateAffectationEnd', $dateAffectationEnd);
        }

        if (!empty($signalementListQueryParams->codeInsee)) {
            $qb->andWhere('s.inseeOccupant = :codeInsee')
                ->setParameter('codeInsee', $signalementListQueryParams->codeInsee);
        }

        $qb->orderBy('s.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function findForAPIQueryBuilder(User $user, ?bool $includeCreatedByUser = false): QueryBuilder
    {
        $partners = $this->partnerAuthorizedResolver->resolveBy($user);
        $qb = $this->createQueryBuilder('s');

        $qb->select('DISTINCT s', 'territory')
            ->leftJoin('s.territory', 'territory')
            ->leftJoin('s.affectations', 'affectations');
        if ($includeCreatedByUser) {
            return $qb->where('affectations.partner IN (:partners) OR s.createdBy = :user')
                ->setParameter('partners', $partners)
                ->setParameter('user', $user);
        }

        return $qb->where('affectations.partner IN (:partners)')
            ->setParameter('partners', $partners);
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception
     * @throws Exception
     */
    public function findSignalementsLastSuiviWithSuiviAuto(Territory $territory, int $limit): array
    {
        $connexion = $this->getEntityManager()->getConnection();
        $sql = 'SELECT s.id, s.reference, s.uuid, MAX(su.created_at) as dernier_suivi_date, MAX(su.created_by_id) as dernier_suivi_created_by, MAX(su.description) as dernier_suivi_description
                FROM signalement s
                INNER JOIN suivi su ON su.signalement_id = s.id
                INNER JOIN territory ON s.territory_id = territory.id
                WHERE s.id in (
                        SELECT sutech.signalement_id FROM suivi sutech WHERE sutech.type = :suiviTypeTechnical
                    )
                AND su.id = (
                        SELECT MAX(su2.id)
                        FROM suivi AS su2
                        WHERE su2.signalement_id = su.signalement_id
                    )
                AND s.statut = :statusSignalement
                AND s.territory_id = :territoryId
                AND su.type = :suiviTypeUsager
                GROUP BY s.id
                HAVING dernier_suivi_date > \''.self::DATE_FEEDBACK_USAGER_ONLINE.'\'
                ORDER BY dernier_suivi_date DESC
                LIMIT '.$limit.';';

        $statement = $connexion->prepare($sql);

        return $statement->executeQuery([
            'statusSignalement' => SignalementStatus::ACTIVE->value,
            'territoryId' => $territory->getId(),
            'suiviTypeTechnical' => Suivi::TYPE_TECHNICAL,
            'suiviTypeUsager' => Suivi::TYPE_USAGER,
        ])->fetchAllAssociative();
    }

    /**
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception
     */
    public function findSignalementsLastSuiviByPartnerOlderThan(Territory $territory, int $limit, int $nbDays): array
    {
        $connexion = $this->getEntityManager()->getConnection();
        $sql = 'SELECT s.id, s.reference, s.uuid, MAX(su.created_at) as dernier_suivi_date, MAX(su.created_by_id) as dernier_suivi_created_by, MAX(su.description) as dernier_suivi_description
                FROM signalement s
                INNER JOIN suivi su ON su.signalement_id = s.id
                INNER JOIN territory ON s.territory_id = territory.id
                WHERE su.id = (
                        SELECT MAX(su2.id)
                        FROM suivi AS su2
                        WHERE su2.signalement_id = su.signalement_id
                    )
                AND s.statut = :statusSignalement
                AND s.territory_id = :territoryId
                AND su.type = :suiviTypePartner
                GROUP BY s.id
                HAVING dernier_suivi_date < NOW() - INTERVAL :nbDays DAY
                ORDER BY dernier_suivi_date DESC
                LIMIT '.$limit.';';

        $statement = $connexion->prepare($sql);

        return $statement->executeQuery([
            'statusSignalement' => SignalementStatus::ACTIVE->value,
            'territoryId' => $territory->getId(),
            'suiviTypePartner' => Suivi::TYPE_PARTNER,
            'nbDays' => $nbDays,
        ])->fetchAllAssociative();
    }

    /**
     * @param array<int, SignalementStatus> $exclusiveStatus
     * @param array<int, SignalementStatus> $excludedStatus
     *
     * @return array<int, Signalement>
     */
    public function findOnSameAddress(
        Signalement $signalement,
        array $exclusiveStatus = [SignalementStatus::NEED_VALIDATION, SignalementStatus::ACTIVE],
        array $excludedStatus = [],
        ?User $createdBy = null,
        ?bool $compareNomOccupant = false,
    ): array {
        $qb = $this->createQueryBuilder('s')
            ->where('s.adresseOccupant = :address')
            ->andWhere('s.cpOccupant = :zipcode')
            ->andWhere('s.villeOccupant = :city')
            ->setParameter('address', $signalement->getAdresseOccupant())
            ->setParameter('zipcode', $signalement->getCpOccupant())
            ->setParameter('city', $signalement->getVilleOccupant());

        if (!empty($exclusiveStatus)) {
            $qb->andWhere('s.statut IN (:exclusiveStatus)')
                ->setParameter('exclusiveStatus', $exclusiveStatus);
        }
        if (!empty($excludedStatus)) {
            $qb->andWhere('s.statut NOT IN (:excludedStatus)')
                ->setParameter('excludedStatus', $excludedStatus);
        }

        if (null !== $signalement->getId()) {
            $qb->andWhere('s.id != :id')
                ->setParameter('id', $signalement->getId());
        }

        if (null !== $createdBy) {
            $qb->andWhere('s.createdBy = :user')
                ->setParameter('user', $createdBy);
        }

        if ($compareNomOccupant && null !== $signalement->getNomOccupant()) {
            $qb->andWhere('s.nomOccupant = :nomOccupant')
                ->setParameter('nomOccupant', $signalement->getNomOccupant());
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */

    /**
     * @throws NonUniqueResultException
     */
    public function findOneForLoginBailleur(string $referenceInjonction, string $loginBailleur): ?Signalement
    {
        $referenceInjonction = str_replace(InjonctionBailleurService::REFERENCE_PREFIX, '', strtoupper($referenceInjonction));

        return $this->createQueryBuilder('s')
            ->leftJoin('s.suivis', 'su')
            ->where('s.referenceInjonction = :referenceInjonction')
            ->setParameter('referenceInjonction', $referenceInjonction)
            ->andWhere('s.loginBailleur = :loginBailleur')
            ->setParameter('loginBailleur', $loginBailleur)
            ->andWhere('s.statut IN (:signalementStatusList) OR su.category IN (:injonctionCategories)')
            ->setParameter('signalementStatusList', SignalementStatus::injonctionStatuses())
            ->setParameter('injonctionCategories', SuiviCategory::injonctionBailleurCategories())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Collection<int, Partner>|null $userPartners
     */
    public function findInjonctionFilteredPaginated(
        SearchSignalementInjonction $searchSignalementInjonction,
        int $maxResult,
        ?Collection $userPartners,
    ): Paginator {
        $queryBuilder = $this->createQueryBuilder('s')
            ->select('s, su')
            ->leftJoin('s.suivis', 'su')
            ->where('s.statut IN (:signalementStatusList)')
            ->setParameter('signalementStatusList', SignalementStatus::injonctionStatuses());

        if (!empty($searchSignalementInjonction->getTerritoire())) {
            $queryBuilder
                ->andWhere('s.territory = :territory')
                ->setParameter('territory', $searchSignalementInjonction->getTerritoire());
        }

        if ($userPartners) {
            $queryBuilder
                ->innerJoin('s.affectations', 'a')
                ->andWhere('a.partner IN (:partners)')
                ->setParameter('partners', $userPartners->toArray());
        }

        if (!empty($searchSignalementInjonction->getReponseBailleur())) {
            if ('aucune' === $searchSignalementInjonction->getReponseBailleur()) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->not(
                        $queryBuilder->expr()->exists(
                            $this->createQueryBuilder('s3')
                                ->select('1')
                                ->join('s3.suivis', 'su3')
                                ->where('s3 = s')
                                ->andWhere('su3.category IN (:aideCategories)')
                                ->getDQL()
                        )
                    )
                );

                $queryBuilder->setParameter(
                    'aideCategories',
                    SuiviCategory::injonctionBailleurReponseCategories()
                );
            } else {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->exists(
                        $this->createQueryBuilder('s2')
                            ->select('1')
                            ->join('s2.suivis', 'su2')
                            ->where('s2 = s')
                            ->andWhere('su2.category = :aideCategory')
                            ->getDQL()
                    )
                );
                $queryBuilder->setParameter('aideCategory', SuiviCategory::tryFrom($searchSignalementInjonction->getReponseBailleur()));
            }
        }

        if (!empty($searchSignalementInjonction->getStatutSignalement())) {
            $queryBuilder->andWhere('s.statut = :statutSignalement')
                ->setParameter('statutSignalement', $searchSignalementInjonction->getStatutSignalement());
        }

        if (!empty($searchSignalementInjonction->getOrderType())) {
            [$orderField, $orderDirection] = explode('-', $searchSignalementInjonction->getOrderType());
            $queryBuilder->orderBy($orderField, $orderDirection);
        } else {
            $queryBuilder->orderBy('s.id', 'DESC');
        }

        $firstResult = ($searchSignalementInjonction->getPage() - 1) * $maxResult;
        $queryBuilder->setFirstResult($firstResult)->setMaxResults($maxResult);

        return new Paginator($queryBuilder->getQuery());
    }

    /**
     * @return Signalement[]
     *
     * @throws \Exception
     */
    public function findInjonctionBeforeDateWithoutAnswer(\DateTimeImmutable $beforeDate): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.statut = :statut')
            ->andWhere('s.createdAt <= :date');

        $qb->andWhere(
            $qb->expr()->not(
                $qb->expr()->exists(
                    $this->createQueryBuilder('s1')
                        ->select('1')
                        ->join('s1.suivis', 'su1')
                        ->where('s1 = s')
                        ->andWhere('su1.category = :ouiCategory OR su1.category = :aideCategory OR su1.category = :demarchesCategory')
                        ->getDQL()
                )
            )
        );
        $qb->setParameter('ouiCategory', SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI)
            ->setParameter('aideCategory', SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI_AVEC_AIDE)
            ->setParameter('demarchesCategory', SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI_DEMARCHES_COMMENCEES);

        $qb->setParameter('statut', SignalementStatus::INJONCTION_BAILLEUR)
            ->setParameter('date', $beforeDate)
            ->orderBy('s.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function findInjonctionToRemindAnswerBailleur(\DateTimeImmutable $beforeDate): array
    {
        $qb = $this->createQueryBuilder('s');
        // Toujours en injonction, donc n'ont pas répondu non ET créés avant la date renseignée ET avec mail proprio présent
        $qb->where('s.statut = :statut')
            ->andWhere('s.createdAt <= :date')
            ->andWhere('s.mailProprio IS NOT NULL');

        // Pas de réponse "oui" ou "oui avec aide" ou "oui démarches commencées
        // ET Pas de rappel déjà envoyé (pas de suivi de catégorie INJONCTION_BAILLEUR_RAPPEL_REPONSE_BAILLEUR)
        $qb->andWhere(
            $qb->expr()->not(
                $qb->expr()->exists(
                    $this->createQueryBuilder('s1')
                        ->select('1')
                        ->join('s1.suivis', 'su1')
                        ->where('s1 = s')
                        ->andWhere('su1.category = :ouiCategory OR su1.category = :aideCategory OR su1.category = :demarchesCategory OR su1.category = :reminderCategory')
                        ->getDQL()
                )
            )
        );
        $qb->setParameter('ouiCategory', SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI)
            ->setParameter('aideCategory', SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI_AVEC_AIDE)
            ->setParameter('demarchesCategory', SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI_DEMARCHES_COMMENCEES)
            ->setParameter('reminderCategory', SuiviCategory::INJONCTION_BAILLEUR_RAPPEL_REPONSE_BAILLEUR);

        $qb->setParameter('statut', SignalementStatus::INJONCTION_BAILLEUR)
            ->setParameter('date', $beforeDate)
            ->orderBy('s.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Signalement[]
     *
     * @throws \Exception
     */
    public function findInjonctionToRemind(\DateTimeImmutable $beforeDate): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.statut = :statut');

        // Au moins une réponse "oui" ou "oui avec aide" ou "oui démarches commencées" avant la date
        $qb->andWhere(
            $qb->expr()->exists(
                $this->createQueryBuilder('s1')
                    ->select('1')
                    ->join('s1.suivis', 'su1')
                    ->where('s1 = s')
                    ->andWhere('su1.category = :ouiCategory OR su1.category = :aideCategory OR su1.category = :demarchesCategory')
                    ->andWhere(
                        $qb->expr()->lt('su1.createdAt', ':date')
                    )
                    ->getDQL()
            )
        );
        $qb->setParameter('ouiCategory', SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI)
            ->setParameter('aideCategory', SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI_AVEC_AIDE)
            ->setParameter('demarchesCategory', SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI_DEMARCHES_COMMENCEES);

        // Aucun rappel envoyé après la date limite
        $qb->andWhere(
            $qb->expr()->not(
                $qb->expr()->exists(
                    $this->createQueryBuilder('s2')
                        ->select('1')
                        ->join('s2.suivis', 'su2')
                        ->where('s2 = s')
                        ->andWhere('su2.category = :reminderCategory')
                        ->andWhere(
                            $qb->expr()->gte('su2.createdAt', ':date')
                        )
                        ->getDQL()
                )
            )
        );
        $qb->setParameter('reminderCategory', SuiviCategory::INJONCTION_BAILLEUR_REMINDER_FOR_USAGER);

        $qb->setParameter('statut', SignalementStatus::INJONCTION_BAILLEUR)
            ->setParameter('date', $beforeDate)
            ->orderBy('s.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function getActiveSignalementsForUser(User $user, ?bool $count = false): array|int
    {
        $qb = $this->createQueryBuilder('s');
        if ($count) {
            $qb->select('COUNT(s.id)');
        } else {
            $qb->select('s');
        }
        $qb->where('s.statut = :statut')
            ->setParameter('statut', SignalementStatus::ACTIVE);

        if ($user->isTerritoryAdmin() || $user->isSuperAdmin()) {
            $qb->andWhere('s.territory IN (:territories)')
                ->setParameter('territories', $user->getPartnersTerritories());
        } else {
            $qb->innerJoin('s.affectations', 'a')
                ->andWhere('a.statut = :affectationStatut')
                ->andWhere('a.partner IN (:partners)')
                ->setParameter('affectationStatut', AffectationStatus::ACCEPTED)
                ->setParameter('partners', $user->getPartners());
        }

        if ($count) {
            return (int) $qb->getQuery()->getSingleScalarResult();
        }

        return $qb->getQuery()->getResult();
    }

    public function getActiveSignalementsWithInteractionsForUser(User $user, ?bool $count = false): array|int
    {
        $qb = $this->createQueryBuilder('s');
        if ($count) {
            $qb->select('COUNT(DISTINCT s.id)');
        } else {
            $qb->select('DISTINCT s');
        }
        $qb
            ->leftJoin('s.suivis', 'su', Join::WITH, 'su.createdBy = :user')
            ->leftJoin('s.affectations', 'aff', Join::WITH, 'aff.answeredBy = :user AND aff.partner IN (:partners)')
            ->where('s.statut = :statut')
            ->andWhere('su.id IS NOT NULL OR aff.id IS NOT NULL')
            ->setParameter('statut', SignalementStatus::ACTIVE)
            ->setParameter('user', $user)
            ->setParameter('partners', $user->getPartners());

        if ($user->isTerritoryAdmin()) {
            $qb->andWhere('s.territory IN (:territories)')
                ->setParameter('territories', $user->getPartnersTerritories());
        } else {
            $qb->innerJoin('s.affectations', 'a')
                ->andWhere('a.statut = :affectationStatut')
                ->andWhere('a.partner IN (:partners)')
                ->setParameter('affectationStatut', AffectationStatus::ACCEPTED);
        }

        if ($count) {
            return (int) $qb->getQuery()->getSingleScalarResult();
        }

        return $qb->getQuery()->getResult();
    }

    public function findWithInconsistentCommuneName(Commune $commune): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.inseeOccupant = :insee')
            ->andWhere('s.villeOccupant != :ville')
            ->setParameter('insee', $commune->getCodeInsee())
            ->setParameter('ville', $commune->getNom());

        return $qb->getQuery()->getResult();
    }
}
