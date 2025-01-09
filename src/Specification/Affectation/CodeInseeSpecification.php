<?php

namespace App\Specification\Affectation;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Zone;
use App\Specification\Context\PartnerSignalementContext;
use App\Specification\Context\SpecificationContextInterface;
use App\Specification\SpecificationInterface;
use Doctrine\Common\Collections\Collection;
use Location\Coordinate;
use Location\Polygon;
use LongitudeOne\Geo\WKT\Parser;

class CodeInseeSpecification implements SpecificationInterface
{
    private const TYPE_POLYGON = 'POLYGON';
    private const TYPE_MULTIPOLYGON = 'MULTIPOLYGON';
    private const TYPE_GEOMETRYCOLLECTION = 'GEOMETRYCOLLECTION';

    public function __construct(private array|string $inseeToInclude, private ?array $inseeToExclude)
    {
        if ('all' !== $inseeToInclude && 'partner_list' !== $inseeToInclude) {
            $this->inseeToInclude = explode(',', $inseeToInclude);
        } else {
            $this->inseeToInclude = $inseeToInclude;
        }
        $this->inseeToExclude = $inseeToExclude;
    }

    private function isExcludedSignalement(Signalement $signalement): bool
    {
        $insee = $signalement->getInseeOccupant();

        return null === $insee || '' === $insee || (!empty($this->inseeToExclude) && \in_array($insee, $this->inseeToExclude));
    }

    public function isSatisfiedBy(SpecificationContextInterface $context): bool
    {
        if (!$context instanceof PartnerSignalementContext) {
            return false;
        }

        /** @var Signalement $signalement */
        $signalement = $context->getSignalement();

        /** @var Partner $partner */
        $partner = $context->getPartner();

        if ($this->isExcludedSignalement($signalement)) {
            return false;
        }

        return match ($this->inseeToInclude) {
            'all' => true,
            'partner_list' => $this->isPartnerListSatisfied($signalement, $partner),
            default => $this->isInseeIncluded($signalement->getInseeOccupant()),
        };
    }

    private function isPartnerListSatisfied(Signalement $signalement, Partner $partner): bool
    {
        $isZoneExcludedOK = true;
        $isInseeOK = $this->isInseeIncludeInPartnerList($partner, $signalement->getInseeOccupant());
        $isZoneOK = $this->isInZone($signalement, $partner->getZones());
        if ($partner->getExcludedZones()->count() > 0) {
            $isZoneExcludedOK = !$this->isInZone($signalement, $partner->getExcludedZones());
        }

        return ($isInseeOK || $isZoneOK) && $isZoneExcludedOK;
    }

    private function isInZone(Signalement $signalement, Collection $zones): bool
    {
        if (0 === $zones->count()) {
            return false;
        }

        foreach ($zones as $zone) {
            if ($this->isSignalementInZone($signalement, $zone)) {
                return true;
            }
        }

        return false;
    }

    private function isInseeIncludeInPartnerList(Partner $partner, string $insee)
    {
        if (0 === \count($partner->getInsee())) {
            return false;
        }

        return \in_array($insee, $partner->getInsee());
    }

    private function isInseeIncluded(string $insee): bool
    {
        return !empty($this->inseeToInclude) && \in_array($insee, $this->inseeToInclude);
    }

    private function isSignalementInZone(Signalement $signalement, Zone $zone): bool
    {
        if (empty($signalement->getGeoloc())) {
            return false;
        }

        $parser = new Parser($zone->getArea());
        $zoneArea = $parser->parse();
        $signalementCoordinate = new Coordinate($signalement->getGeoloc()['lat'], $signalement->getGeoloc()['lng']);

        return match ($zoneArea['type']) {
            self::TYPE_POLYGON => $this->isPointInPolygon($signalementCoordinate, $zoneArea['value']),
            self::TYPE_MULTIPOLYGON => $this->isPointInMultiPolygon($signalementCoordinate, $zoneArea['value']),
            self::TYPE_GEOMETRYCOLLECTION => $this->isPointInGeometryCollection($signalementCoordinate, $zoneArea['value']),
            default => false,
        };
    }

    private function isPointInPolygon(Coordinate $point, array $polygonData): bool
    {
        $polygon = $this->buildPolygon($polygonData);

        return $polygon->contains($point);
    }

    private function isPointInMultiPolygon(Coordinate $point, array $multiPolygonData): bool
    {
        foreach ($multiPolygonData as $polygonData) {
            if ($this->isPointInPolygon($point, $polygonData)) {
                return true;
            }
        }

        return false;
    }

    private function isPointInGeometryCollection(Coordinate $point, array $geometryCollection): bool
    {
        foreach ($geometryCollection as $geometry) {
            if (self::TYPE_POLYGON === $geometry['type'] && $this->isPointInPolygon($point, $geometry['value'])) {
                return true;
            }
            if (self::TYPE_MULTIPOLYGON === $geometry['type'] && $this->isPointInMultiPolygon($point, $geometry['value'])) {
                return true;
            }
        }

        return false;
    }

    private function buildPolygon(array $points): Polygon
    {
        $geofence = new Polygon();
        if (1 === \count($points) && \count($points[0]) > 2) {
            $points = $points[0];
        }
        foreach ($points as $point) {
            $geofence->addPoint(new Coordinate($point[1], $point[0]));
        }

        return $geofence;
    }
}
