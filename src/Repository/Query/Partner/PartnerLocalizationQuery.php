<?php

namespace App\Repository\Query\Partner;

use App\Entity\Enum\Qualification;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Repository\AffectationRepository;
use App\Repository\PartnerRepository;
use Doctrine\DBAL\ArrayParameterType;
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
                AND NOT EXISTS (
                    SELECT 1 FROM partner_excluded_zone pez
                    INNER JOIN zone ez ON ez.id = pez.zone_id
                    WHERE pez.partner_id = p.id
                    AND ST_Contains(ez.area, Point(:lng, :lat))
                )
                '.$clauseSubquery.'
                '.$whereCompetenceInjonctionBailleur.'
                ORDER BY p.nom ASC';

        return [
            'sql' => $sql,
            'params' => $params,
        ];
    }

    /**
     * Variante « en masse » de buildLocalizationQuery() (utilisée par l'affectation en masse) :
     * parmi $signalementIds, renvoie ceux qui sont dans le périmètre de $partner, en une seule requête.
     *
     * ⚠️ La règle de périmètre existe en deux exemplaires : toute évolution de buildLocalizationQuery()
     * doit être reportée ici (et inversement). PartnerLocalizationQueryTest vérifie que les deux concordent.
     *
     * Géolocalisation : même logique que Signalement::getGeoloc() (s.geoloc, sinon address.point).
     * Sans géolocalisation, le signalement n'est dans aucune zone ni zone exclue.
     * partner.insee est une colonne JSON : la comparer avec LIKE (cast texte), jamais avec = ou IN.
     *
     * @param array<int> $signalementIds
     *
     * @return array<int>
     *
     * @throws Exception
     */
    public function findSignalementIdsInPartnerPerimeter(Partner $partner, array $signalementIds): array
    {
        if (empty($signalementIds)) {
            return [];
        }

        $sql = '
            SELECT loc.id
            FROM (
                SELECT s.id,
                       a.city_code,
                       a.territory_id,
                       COALESCE(
                           Point(
                               CAST(JSON_UNQUOTE(JSON_EXTRACT(s.geoloc, \'$.lng\')) AS DECIMAL(10,7)),
                               CAST(JSON_UNQUOTE(JSON_EXTRACT(s.geoloc, \'$.lat\')) AS DECIMAL(10,7))
                           ),
                           a.point
                       ) AS pt
                FROM signalement s
                INNER JOIN address a ON a.id = s.address_id
                WHERE s.id IN (:signalementIds)
            ) loc
            INNER JOIN partner p ON p.id = :partner
            WHERE p.is_archive = 0
            AND p.territory_id = loc.territory_id
            AND (
                -- 1. commune listée dans les INSEE du partenaire
                (
                    p.insee IS NOT NULL
                    AND p.insee NOT LIKE \'[]\'
                    AND p.insee NOT LIKE \'[""]\'
                    AND p.insee LIKE CONCAT(\'%\', loc.city_code, \'%\')
                )
                -- 2. point dans une zone du partenaire
                OR EXISTS (
                    SELECT 1 FROM partner_zone pz
                    INNER JOIN zone z ON z.id = pz.zone_id
                    WHERE pz.partner_id = p.id
                    AND loc.pt IS NOT NULL
                    AND ST_Contains(z.area, loc.pt)
                )
                -- 3. partenaire sans périmètre défini = tout le territoire
                OR (
                    (p.insee IS NULL OR p.insee LIKE \'[]\' OR p.insee LIKE \'[""]\')
                    AND NOT EXISTS (SELECT 1 FROM partner_zone pz WHERE pz.partner_id = p.id)
                    AND NOT EXISTS (SELECT 1 FROM partner_epci pe WHERE pe.partner_id = p.id)
                )
                -- 4. commune dans un EPCI du partenaire
                OR EXISTS (
                    SELECT 1 FROM partner_epci pe
                    INNER JOIN commune c ON c.epci_id = pe.epci_id
                    WHERE pe.partner_id = p.id
                    AND c.code_insee = loc.city_code
                )
            )
            -- point hors de toutes les zones exclues du partenaire
            AND NOT EXISTS (
                SELECT 1 FROM partner_excluded_zone pez
                INNER JOIN zone ez ON ez.id = pez.zone_id
                WHERE pez.partner_id = p.id
                AND loc.pt IS NOT NULL
                AND ST_Contains(ez.area, loc.pt)
            )
        ';

        return array_map('intval', $this->entityManager->getConnection()->executeQuery(
            $sql,
            ['signalementIds' => $signalementIds, 'partner' => $partner->getId()],
            ['signalementIds' => ArrayParameterType::INTEGER]
        )->fetchFirstColumn());
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
