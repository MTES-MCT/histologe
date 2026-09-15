<?php

namespace App\Repository\Query\Partner;

use App\Entity\Enum\Qualification;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Repository\AffectationRepository;
use App\Repository\PartnerRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;

class PartnerLocalizationQuery
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AffectationRepository $affectationRepository,
        private readonly PartnerRepository $partnerRepository,
    ) {
    }

    /**
     * @return array<int, array<string, int|string>>
     *
     * @throws Exception
     */
    public function findByLocalization(Signalement $signalement, bool $affected = true, bool $filterInjonctionBailleur = false): array
    {
        $queryData = $this->buildLocalizationQuery($signalement, $affected, $filterInjonctionBailleur);

        $resultSet = $this->entityManager->getConnection()->executeQuery(
            $queryData['sql'],
            $queryData['params']
        );

        return $resultSet->fetchAllAssociative();
    }

    /**
     * Builds the SQL query and parameters for localization search.
     *
     * @return array<int|string, mixed>
     *
     * @throws Exception
     */
    public function buildLocalizationQuery(Signalement $signalement, bool $affected, bool $filterInjonctionBailleur = false): array
    {
        $operator = $affected ? 'IN' : 'NOT IN';

        $subquery = $this->affectationRepository->createQueryBuilder('a')
            ->select('IDENTITY(a.partner)')
            ->where('a.signalement = :signalement')
            ->setParameter('signalement', $signalement);

        $affectedPartners = $subquery->getQuery()->getSingleColumnResult();

        $params = [
            'territory' => $signalement->getAddress()->getTerritory()->getId(),
            'insee_like' => '%'.$signalement->getAddress()->getCityCode().'%',
            'insee' => $signalement->getAddress()->getCityCode(),
            'lng' => $signalement->getGeoloc()['lng'] ?? 'notInZone',
            'lat' => $signalement->getGeoloc()['lat'] ?? 'notInZone',
        ];

        $clauseSubquery = '';
        if (\count($affectedPartners) || 'IN' === $operator) {
            if (0 === \count($affectedPartners)) {
                $clauseSubquery = 'AND p.id '.$operator.' (null)';
            } else {
                $partnersParams = [];
                foreach ($affectedPartners as $key => $partner) {
                    $partnersParams[] = ':partner_'.$key;
                    $params['partner_'.$key] = $partner;
                }
                $clauseSubquery = 'AND p.id '.$operator.' ('.implode(',', $partnersParams).')';
            }
        }

        $whereCompetenceInjonctionBailleur = '';
        if ($filterInjonctionBailleur) {
            $whereCompetenceInjonctionBailleur = 'AND p.competence LIKE "%'.Qualification::AIDE_BAILLEURS->name.'%" ';
        }

        $sql = '
                SELECT DISTINCT p.id, p.nom as name, p.type
                FROM partner p
                LEFT JOIN partner_zone pz ON p.id = pz.partner_id
                LEFT JOIN zone z ON pz.zone_id = z.id
                LEFT JOIN partner_excluded_zone pez ON p.id = pez.partner_id
                LEFT JOIN zone ez ON pez.zone_id = ez.id
                LEFT JOIN partner_epci pe ON p.id = pe.partner_id
                LEFT JOIN commune c ON c.epci_id = pe.epci_id
                WHERE p.is_archive = 0
                AND p.territory_id = :territory
                AND (
                    (
                        p.insee IS NOT NULL
                        AND p.insee != \'[]\'
                        AND p.insee != \'[""]\'
                        AND p.insee LIKE :insee_like
                    )
                    OR (
                        z.id IS NOT NULL
                        AND ST_Contains(z.area, Point(:lng, :lat))
                    )
                    OR (
                        (p.insee IS NULL OR p.insee LIKE \'[]\' OR p.insee LIKE \'[""]\' )
                        AND z.id IS NULL
                        AND pe.epci_id IS NULL
                    )
                    OR (c.code_insee = :insee)
                )
                AND (ez.id IS NULL OR NOT ST_Contains(ez.area, Point(:lng, :lat)))
                '.$clauseSubquery.'
                '.$whereCompetenceInjonctionBailleur.'
                ORDER BY p.nom ASC';

        return [
            'sql' => $sql,
            'params' => $params,
        ];
    }

    /**
     * @return array<int, Partner>
     *
     * @throws Exception
     */
    public function findPartnersByLocalization(Signalement $signalement, bool $addAffectedPartner = false): array
    {
        $queryData = $this->buildLocalizationQuery($signalement, false); // Always use $affected = false

        $resultSet = $this->entityManager->getConnection()->executeQuery(
            $queryData['sql'],
            $queryData['params']
        );
        $partnerIds = array_column($resultSet->fetchAllAssociative(), 'id');
        if ($addAffectedPartner) {
            $queryData = $this->buildLocalizationQuery($signalement, true);
            $resultSet = $this->entityManager->getConnection()->executeQuery(
                $queryData['sql'],
                $queryData['params']
            );
            $partnerIds = array_merge($partnerIds, array_column($resultSet->fetchAllAssociative(), 'id'));
        }

        return $this->partnerRepository->findBy(['id' => $partnerIds]);
    }
}
