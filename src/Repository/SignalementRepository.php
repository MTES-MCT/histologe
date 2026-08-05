<?php

namespace App\Repository;

use App\Dto\Api\Request\SignalementListQueryParams;
use App\Entity\Commune;
use App\Entity\EmailDeliveryIssue;
use App\Entity\Enum\AffectationStatus;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Notification;
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
use App\Utils\Address\AddressParser;
use App\Utils\Address\CommuneHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\TransactionRequiredException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Signalement>
 *
 * @method Signalement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Signalement|null findOneBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null)
 * @method Signalement[]    findAll()
 * @method Signalement[]    findBy(array<string, mixed> $criteria, array<string, mixed>|null $orderBy = null, $limit = null, $offset = null)
 */
class SignalementRepository extends ServiceEntityRepository
{
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
        return $this->findCommunes($user, $territory, 'address.city', 'city');
    }

    /**
     * @return array<int, array<string, mixed>>|int|string
     */
    public function findZipcodes(User $user, ?Territory $territory = null): array|int|string
    {
        return $this->findCommunes($user, $territory, 'address.postCode', 'zipcode');
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
            ->innerJoin('s.address', 'address')
            ->where('s.statut NOT IN (:statutList)')
            ->setParameter('statutList', SignalementStatus::excludedStatuses());
        if (!$user->isSuperAdmin() && !$user->isTerritoryAdmin()) {
            $qb->leftJoin('s.affectations', 'affectations')
                ->leftJoin('affectations.partner', 'partner')
                ->andWhere('partner IN (:partners)')
                ->setParameter('partners', $user->getPartners());
        }
        if ($territory) {
            $qb->andWhere('address.territory = :territory')
                ->setParameter('territory', $territory);
        } elseif (!$user->isSuperAdmin()) {
            $qb->andWhere('address.territory IN (:territories)')
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
        $parsedAddress = AddressParser::parse($address);
        $houseNumber = $parsedAddress['number'];
        if ($houseNumber && $parsedAddress['suffix']) {
            $houseNumber .= ' '.$parsedAddress['suffix'];
        }
        $street = mb_trim($parsedAddress['street']);

        $qb = $this->createQueryBuilder('s')
            ->innerJoin('s.address', 'address')
            ->andWhere('s.mailDeclarant = :email OR s.mailOccupant = :email')->setParameter('email', $email)
            ->andWhere('address.street = :street')->setParameter('street', $street)
            ->andWhere('address.postCode = :zipcode')->setParameter('zipcode', $zipcode)
            ->andWhere('address.city = :city')->setParameter('city', $city)
            ->andWhere('s.statut NOT IN (:statutList)')->setParameter('statutList', SignalementStatus::excludedStatuses());
        if ($houseNumber) {
            $qb->andWhere('address.housenumber = :housenumber')->setParameter('housenumber', $houseNumber);
        } else {
            $qb->andWhere('address.housenumber IS NULL');
        }

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
        $qb->innerJoin('s.address', 'address');
        if ($isTiersDeclarant) {
            $qb
                ->andWhere('s.mailDeclarant = :email')->setParameter('email', $email)
                ->andWhere('s.nomOccupant = :nomOccupant')->setParameter('nomOccupant', $nomOccupant);
        } else {
            $qb->andWhere('s.mailOccupant = :email')->setParameter('email', $email);
        }

        $parsedAddress = AddressParser::parse($address);
        $houseNumber = $parsedAddress['number'];
        if ($houseNumber && $parsedAddress['suffix']) {
            $houseNumber .= ' '.$parsedAddress['suffix'];
        }
        $street = mb_trim($parsedAddress['street']);
        if ($houseNumber) {
            $qb->andWhere('address.housenumber = :housenumber')->setParameter('housenumber', $houseNumber);
        } else {
            $qb->andWhere('address.housenumber IS NULL');
        }
        $qb->andWhere('LOWER(address.street) = :address')->setParameter('address', strtolower($street))
            ->andWhere('address.postCode = :zipcode')->setParameter('zipcode', $zipcode)
            ->andWhere('LOWER(address.city) = :city')->setParameter('city', strtolower($city))
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
        $page = (int) $signalementListQueryParams->page;
        $limit = (int) $signalementListQueryParams->limit;

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
            ->innerJoin('s.address', 'address')
            ->andWhere('address.street = :street')
            ->andWhere('address.postCode = :zipcode')
            ->andWhere('address.cityCode = :insee')
            ->setParameter('street', $signalement->getAddress()->getStreet())
            ->setParameter('zipcode', $signalement->getAddress()->getPostCode())
            ->setParameter('insee', $signalement->getAddress()->getCityCode());

        if ($signalement->getAddress()->getHousenumber()) {
            $qb->andWhere('address.housenumber = :housenumber')
                ->setParameter('housenumber', $signalement->getAddress()->getHousenumber());
        } else {
            $qb->andWhere('a.housenumber IS NULL');
        }

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
     *
     * @return Paginator<Signalement>
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

        if (!empty($searchSignalementInjonction->getMessages())) {
            $categories = 'usager' === $searchSignalementInjonction->getMessages()
                ? SuiviCategory::categoriesSubmittedByUsager()
                : SuiviCategory::categoriesSubmittedByBailleur();

            $notificationQueryBuilder = $this->getEntityManager()->createQueryBuilder()
                    ->select('1')
                    ->from(Notification::class, 'n')
                    ->join('n.suivi', 'n_suivi')
                    ->where('n.signalement = s')
                    ->andWhere('n.user = :currentUser')
                    ->andWhere('n.isSeen = false')
                    ->andWhere('n.deleted = false')
                    ->andWhere('n_suivi.category IN (:messageCategories)');

            $queryBuilder->andWhere(
                $queryBuilder->expr()->exists($notificationQueryBuilder->getDQL())
            )
            ->setParameter('currentUser', $searchSignalementInjonction->getUser())
            ->setParameter('messageCategories', $categories);
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

    /**
     * @return array<Signalement>
     */
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
    public function findInjonctionToRemind(
        \DateTimeImmutable $beforeDate,
        string $recipient, // bailleur ou usager
    ): array {
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

        // Aucun suivi de la partie concernée ni de rappel envoyé depuis la date limite
        $qb->andWhere(
            $qb->expr()->not(
                $qb->expr()->exists(
                    $this->createQueryBuilder('s2')
                        ->select('1')
                        ->join('s2.suivis', 'su2')
                        ->where('s2 = s')
                        ->andWhere('su2.category IN (:category_list)')
                        ->andWhere($qb->expr()->gte('su2.createdAt', ':date'))
                        ->getDQL()
                )
            )
        );
        $isUsager = 'usager' === $recipient;
        // Aucune demande de clôture de la part du bailleur en cours : le suivi mensuel classique est alors
        // remplacé par les relances dédiées de la démarche de clôture (côté usager comme côté bailleur).
        $qb->andWhere(
            $qb->expr()->not(
                $qb->expr()->exists(
                    $this->createQueryBuilder('s3')
                        ->select('1')
                        ->join('s3.suivis', 'su3')
                        ->where('s3 = s')
                        ->andWhere('su3.category = :category_demande_cloture')
                        ->getDQL()
                )
            )
        );
        $qb->setParameter('category_demande_cloture', SuiviCategory::INJONCTION_BAILLEUR_DEMANDE_CLOTURE_PAR_BAILLEUR);
        $qb->setParameter('category_list', $isUsager
            ? array_merge(SuiviCategory::categoriesSubmittedByUsager(), [SuiviCategory::INJONCTION_BAILLEUR_REMINDER_FOR_USAGER])
            : array_merge(SuiviCategory::categoriesSubmittedByBailleur(), [SuiviCategory::INJONCTION_BAILLEUR_REMINDER_FOR_BAILLEUR]));

        $qb->setParameter('statut', SignalementStatus::INJONCTION_BAILLEUR)
            ->setParameter('date', $beforeDate)
            ->orderBy('s.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Dossiers dont le bailleur a demandé la clôture avant $beforeDate, toujours en statut
     * INJONCTION_BAILLEUR (donc sans décision de l'usager), et n'ayant pas déjà reçu la relance de clôture.
     *
     * @return Signalement[]
     *
     * @throws \Exception
     */
    public function findInjonctionClotureBailleurToRemindUsager(
        \DateTimeImmutable $beforeDate,
    ): array {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.statut = :statut');

        // Une demande de clôture du bailleur, faite avant la date
        $qb->andWhere(
            $qb->expr()->exists(
                $this->createQueryBuilder('s1')
                    ->select('1')
                    ->join('s1.suivis', 'su1')
                    ->where('s1 = s')
                    ->andWhere('su1.category = :category_demande_cloture')
                    ->andWhere(
                        $qb->expr()->lt('su1.createdAt', ':date')
                    )
                    ->getDQL()
            )
        );
        // Pas encore de relance envoyée
        $qb->andWhere(
            $qb->expr()->not(
                $qb->expr()->exists(
                    $this->createQueryBuilder('s2')
                        ->select('1')
                        ->join('s2.suivis', 'su2')
                        ->where('s2 = s')
                        ->andWhere('su2.category = :category_relance')
                        ->getDQL()
                )
            )
        );

        $qb->setParameter('category_demande_cloture', SuiviCategory::INJONCTION_BAILLEUR_DEMANDE_CLOTURE_PAR_BAILLEUR)
            ->setParameter('category_relance', SuiviCategory::INJONCTION_BAILLEUR_RELANCE_USAGER_CLOTURE)
            ->setParameter('statut', SignalementStatus::INJONCTION_BAILLEUR)
            ->setParameter('date', $beforeDate)
            ->orderBy('s.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Dossiers pour lesquels la relance de clôture a été envoyée à l'usager avant $beforeDate,
     * toujours en statut INJONCTION_BAILLEUR (donc sans décision de l'usager).
     *
     * @return Signalement[]
     *
     * @throws \Exception
     */
    public function findInjonctionClotureBailleurToClose(
        \DateTimeImmutable $beforeDate,
    ): array {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.statut = :statut');

        $qb->andWhere(
            $qb->expr()->exists(
                $this->createQueryBuilder('s1')
                    ->select('1')
                    ->join('s1.suivis', 'su1')
                    ->where('s1 = s')
                    ->andWhere('su1.category = :category_relance')
                    ->andWhere(
                        $qb->expr()->lt('su1.createdAt', ':date')
                    )
                    ->getDQL()
            )
        );

        $qb->setParameter('category_relance', SuiviCategory::INJONCTION_BAILLEUR_RELANCE_USAGER_CLOTURE)
            ->setParameter('statut', SignalementStatus::INJONCTION_BAILLEUR)
            ->setParameter('date', $beforeDate)
            ->orderBy('s.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Dossiers en démarche accélérée sans aucune activité : 3 relances consécutives sans réponse
     * du bailleur ET 3 relances consécutives sans réponse de l'usager (chacune datant d'avant
     * $beforeDate), avec un email exploitable pour notifier la clôture.
     *
     * @return Signalement[]
     */
    public function findInjonctionToCloseWithoutActivity(\DateTimeImmutable $beforeDate): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.statut = :statut')
            ->setParameter('statut', SignalementStatus::INJONCTION_BAILLEUR);

        // Réponse initiale du bailleur (le dossier est bien entré en phase de suivi des travaux)
        $qb->andWhere(
            $qb->expr()->exists(
                $this->createQueryBuilder('s1')
                    ->select('1')
                    ->join('s1.suivis', 'su1')
                    ->where('s1 = s')
                    ->andWhere('su1.category IN (:reponseInitialeCategories)')
                    ->getDQL()
            )
        );
        $qb->setParameter('reponseInitialeCategories', [
            SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI,
            SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI_AVEC_AIDE,
            SuiviCategory::INJONCTION_BAILLEUR_REPONSE_OUI_DEMARCHES_COMMENCEES,
        ]);

        // Au moins 3 relances bailleur consécutives depuis sa dernière réponse, chacune ≤ beforeDate
        $qb->andWhere(
            $qb->expr()->exists(
                $this->createQueryBuilder('s2')
                    ->select('1')
                    ->join('s2.suivis', 'su2')
                    ->where('s2 = s')
                    ->andWhere('su2.category = :reminderBailleur')
                    ->andWhere('su2.createdAt <= :beforeDate')
                    ->andWhere(
                        $qb->expr()->orX(
                            $qb->expr()->not(
                                $qb->expr()->exists(
                                    $this->createQueryBuilder('s2resp')
                                        ->select('1')
                                        ->join('s2resp.suivis', 'su2resp')
                                        ->where('s2resp = s2')
                                        ->andWhere('su2resp.category IN (:reponseBailleurCategories)')
                                        ->getDQL()
                                )
                            ),
                            'su2.createdAt > (SELECT MAX(subB.createdAt) FROM '.Suivi::class.' subB WHERE subB.signalement = s2 AND subB.category IN (:reponseBailleurCategories))'
                        )
                    )
                    ->groupBy('s2.id')
                    ->having('COUNT(su2.id) >= 3')
                    ->getDQL()
            )
        );

        // Au moins 3 relances usager consécutives depuis sa dernière réponse, chacune ≤ beforeDate
        $qb->andWhere(
            $qb->expr()->exists(
                $this->createQueryBuilder('s3')
                    ->select('1')
                    ->join('s3.suivis', 'su3')
                    ->where('s3 = s')
                    ->andWhere('su3.category = :reminderUsager')
                    ->andWhere('su3.createdAt <= :beforeDate')
                    ->andWhere(
                        $qb->expr()->orX(
                            $qb->expr()->not(
                                $qb->expr()->exists(
                                    $this->createQueryBuilder('s3resp')
                                        ->select('1')
                                        ->join('s3resp.suivis', 'su3resp')
                                        ->where('s3resp = s3')
                                        ->andWhere('su3resp.category IN (:reponseUsagerCategories)')
                                        ->getDQL()
                                )
                            ),
                            'su3.createdAt > (SELECT MAX(subU.createdAt) FROM '.Suivi::class.' subU WHERE subU.signalement = s3 AND subU.category IN (:reponseUsagerCategories))'
                        )
                    )
                    ->groupBy('s3.id')
                    ->having('COUNT(su3.id) >= 3')
                    ->getDQL()
            )
        );

        $qb->setParameter('reminderBailleur', SuiviCategory::INJONCTION_BAILLEUR_REMINDER_FOR_BAILLEUR)
            ->setParameter('reminderUsager', SuiviCategory::INJONCTION_BAILLEUR_REMINDER_FOR_USAGER)
            ->setParameter('reponseBailleurCategories', SuiviCategory::categoriesSubmittedByBailleur())
            ->setParameter('reponseUsagerCategories', SuiviCategory::categoriesSubmittedByUsager())
            ->setParameter('beforeDate', $beforeDate);

        // Au moins un email exploitable (mailProprio ou mailOccupant), sans problème de distribution
        $qb->andWhere(
            $qb->expr()->orX(
                $qb->expr()->andX(
                    $qb->expr()->isNotNull('s.mailProprio'),
                    $qb->expr()->not($qb->expr()->exists(
                        $this->getEntityManager()->createQueryBuilder()
                            ->select('1')
                            ->from(EmailDeliveryIssue::class, 'edi1')
                            ->where('edi1.email = s.mailProprio')
                            ->getDQL()
                    ))
                ),
                $qb->expr()->andX(
                    $qb->expr()->isNotNull('s.mailOccupant'),
                    $qb->expr()->not($qb->expr()->exists(
                        $this->getEntityManager()->createQueryBuilder()
                            ->select('1')
                            ->from(EmailDeliveryIssue::class, 'edi2')
                            ->where('edi2.email = s.mailOccupant')
                            ->getDQL()
                    ))
                )
            )
        );

        $qb->orderBy('s.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<Signalement>|int
     */
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

    /**
     * @return array<Signalement>|int
     */
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

    /**
     * @return array<Signalement>
     */
    public function findWithInconsistentCommuneName(Commune $commune): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->where('s.inseeOccupant = :insee')
            ->andWhere('s.villeOccupant != :ville')
            ->setParameter('insee', $commune->getCodeInsee())
            ->setParameter('ville', $commune->getNom());

        return $qb->getQuery()->getResult();
    }

    public function findOneByUuidWithSuivis(string $uuid): ?Signalement
    {
        $qb = $this->createQueryBuilder('s');
        $qb->select('s', 'su');
        $qb->leftJoin('s.suivis', 'su');
        $qb->where('s.uuid = :uuid')->setParameter('uuid', $uuid);

        return $qb->getQuery()->getOneOrNullResult();
    }

    // utilisation uniquement dans les tests
    public function findByAddress(?string $housenumber, string $street, string $postCode, string $city): array
    {
        $qb = $this->createQueryBuilder('s');
        if ($housenumber) {
            $qb->andWhere('s.address.housenumber = :housenumber')
                ->setParameter('housenumber', $housenumber);
        } else {
            $qb->andWhere('s.address.housenumber IS NULL');
        }
        $qb->andWhere('s.address.street = :street')
            ->andWhere('s.address.postCode = :postCode')
            ->andWhere('s.address.city = :city')
            ->setParameter('street', $street)
            ->setParameter('postCode', $postCode)
            ->setParameter('city', $city);

        $qb->orderBy('s.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }
}
