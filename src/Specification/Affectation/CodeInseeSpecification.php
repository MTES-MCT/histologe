<?php

namespace App\Specification\Affectation;

use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\Zone;
use App\Specification\Context\PartnerSignalementContext;
use App\Specification\Context\SpecificationContextInterface;
use App\Specification\SpecificationInterface;
use Location\Coordinate;
use Location\Polygon;
use LongitudeOne\Geo\WKT\Parser;

class CodeInseeSpecification implements SpecificationInterface
{
    public function __construct(private array|string $inseeToInclude, private ?array $inseeToExclude)
    {
        if ('all' !== $inseeToInclude && 'partner_list' !== $inseeToInclude) {
            $this->inseeToInclude = explode(',', $inseeToInclude);
        } else {
            $this->inseeToInclude = $inseeToInclude;
        }
        $this->inseeToExclude = $inseeToExclude;
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

        if (null === $signalement->getInseeOccupant()
            || '' === $signalement->getInseeOccupant()
            || !empty($this->inseeToExclude) && \in_array($signalement->getInseeOccupant(), $this->inseeToExclude)) {
            return false;
        }

        $result = false;

        switch ($this->inseeToInclude) {
            case 'all':
                $result = true;
                break;
            case 'partner_list':
                $isZoneOK = true;
                $isZoneExcludedOK = true;
                $isInseeOK = !empty($partner->getInsee())
                    && \in_array($signalement->getInseeOccupant(), $partner->getInsee());
                // var_dump($isInseeOK);
                if ($isInseeOK) {
                    if (!empty($partner->getZones()) && $partner->getZones()->count() > 0) {
                        $isZoneOK = false;
                        foreach ($partner->getZones() as $zone) {
                            $isZoneOK = $this->isSignalementInZone($signalement, $zone);
                            if ($isZoneOK) {
                                break;
                            }
                        }
                    }
                    // var_dump($isZoneOK);
                    if ($isZoneOK && !empty($partner->getExcludedZones()) && $partner->getExcludedZones()->count() > 0) {
                        foreach ($partner->getExcludedZones() as $zoneExcluded) {
                            $isZoneExcludedOK = !$this->isSignalementInZone($signalement, $zoneExcluded);
                            if (!$isZoneExcludedOK) {
                                break;
                            }
                        }
                    }
                    // var_dump($isZoneExcludedOK);
                }

                $result = $isInseeOK && $isZoneOK && $isZoneExcludedOK;
                break;
            default:
                $result = !empty($this->inseeToInclude)
                    && \in_array($signalement->getInseeOccupant(), $this->inseeToInclude);
                break;
        }

        return $result;
    }

    private function isSignalementInZone(Signalement $signalement, Zone $zone): bool
    {
        $parser = new Parser($zone->getArea());
        $zoneArea = $parser->parse();
        $signalementCoordinate = new Coordinate($signalement->getGeoloc()['lat'], $signalement->getGeoloc()['lng']);
        // var_dump($signalement->getGeoloc());
        if ('POLYGON' === $zoneArea['type']) {
            $polygon = $this->buildPolygon($zoneArea['value']);
            // var_dump($polygon->contains($signalementCoordinate));
            if ($polygon->contains($signalementCoordinate)) {
                return true;
            }
        }
        if ('MULTIPOLYGON' === $zoneArea['type']) {
            foreach ($zoneArea['value'] as $value) {
                $polygon = $this->buildPolygon($value);
                if ($polygon->contains($signalementCoordinate)) {
                    return true;
                }
            }
        }
        if ('GEOMETRYCOLLECTION' === $zoneArea['type']) {
            foreach ($zoneArea['value'] as $value) {
                if ('POLYGON' === $value['type']) {
                    $polygon = $this->buildPolygon($value['value']);
                    if ($polygon->contains($signalementCoordinate)) {
                        return true;
                    }
                }
                if ('MULTIPOLYGON' === $value['type']) {
                    foreach ($value['value'] as $val) {
                        $polygon = $this->buildPolygon($val);
                        if ($polygon->contains($signalementCoordinate)) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    private function buildPolygon(array $points): Polygon
    {
        $geofence = new Polygon();
        // var_dump($points);
        if (1 === \count($points) && \count($points[0]) > 2) {
            $points = $points[0];
        }
        // var_dump($points);
        foreach ($points as $point) {
            $geofence->addPoint(new Coordinate($point[1], $point[0]));
        }

        // var_dump($geofence->getPoints());
        return $geofence;
    }
}
