<?php

namespace App\Service\Geometry;

use LongitudeOne\Geo\WKT\Parser;
use LongitudeOne\Spatial\PHP\Types\Geometry\GeometryInterface;

/**
 * Factory service for creating Geometry objects from WKT strings.
 */
class GeometryFactory
{
    /**
     * Map of WKT type names to Geometry class names.
     */
    public const TYPE_MAP = [
        'POINT' => 'Point',
        'LINESTRING' => 'LineString',
        'POLYGON' => 'Polygon',
        'MULTIPOINT' => 'MultiPoint',
        'MULTILINESTRING' => 'MultiLineString',
        'MULTIPOLYGON' => 'MultiPolygon',
    ];

    /**
     * Creates a Geometry object from a WKT string.
     *
     * @param string $wkt The WKT string (e.g., "POLYGON((0 0, 0 10, 10 10, 10 0, 0 0))")
     *
     * @return GeometryInterface The geometry object (Polygon, MultiPolygon, etc.)
     *
     * @throws \InvalidArgumentException If the WKT format is invalid or unsupported
     */
    public function createFromWkt(string $wkt): GeometryInterface
    {
        try {
            $parser = new Parser(trim($wkt));
            $parsed = $parser->parse();

            if ('GEOMETRYCOLLECTION' === $parsed['type']) {
                if (empty($parsed['value'])) {
                    throw new \InvalidArgumentException('Empty GEOMETRYCOLLECTION');
                }

                $geometries = [];
                foreach ($parsed['value'] as $geometryData) {
                    if (!\is_array($geometryData) || !isset($geometryData['type']) || !isset($geometryData['value'])) {
                        throw new \InvalidArgumentException('Invalid GEOMETRYCOLLECTION structure');
                    }
                    // Build properly typed array for PHPStan
                    /** @var array{type: string, value: mixed, srid?: int|null, dimension?: string|null} $typedGeometryData */
                    $typedGeometryData = [
                        'type' => $geometryData['type'],
                        'value' => $geometryData['value'],
                        'srid' => $geometryData['srid'] ?? null,
                        'dimension' => $geometryData['dimension'] ?? null,
                    ];
                    $geometries[] = $this->createFromParsedData($typedGeometryData);
                }

                $collection = new GeometryCollection($geometries, $parsed['srid'] ?? null);

                return $collection->simplify();
            }

            return $this->createFromParsedData($parsed);
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(sprintf('Failed to parse WKT string: %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * Creates a Geometry object from parsed WKT data.
     *
     * @param array{type: string, value: mixed, srid?: ?int, dimension?: ?string} $parsed Parsed WKT data from Parser
     *
     * @return GeometryInterface The geometry object
     *
     * @throws \InvalidArgumentException If the geometry type is unsupported
     */
    public function createFromParsedData(array $parsed): GeometryInterface
    {
        $type = $parsed['type'];

        if (!isset(self::TYPE_MAP[$type])) {
            throw new \InvalidArgumentException(sprintf('Unsupported geometry type: %s', $type));
        }

        $className = 'LongitudeOne\\Spatial\\PHP\\Types\\Geometry\\'.self::TYPE_MAP[$type];

        return new $className($parsed['value'], $parsed['srid'] ?? null);
    }

    /**
     * Converts a Geometry object to a complete WKT string.
     *
     * @param GeometryInterface $geometry The geometry object
     *
     * @return string The WKT string (e.g., "POLYGON((0 0, 0 10, 10 10, 10 0, 0 0))")
     */
    public function toWkt(GeometryInterface $geometry): string
    {
        // __toString() returns coordinates only, we need to add the type prefix
        return strtoupper($geometry->getType()).'('.(string) $geometry.')';
    }
}
