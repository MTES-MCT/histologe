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
        $wkt = $this->normalizeWkt($wkt);

        try {
            $parser = new Parser($wkt);
            $parsed = $parser->parse();

            return $this->createFromParsedData($parsed);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(sprintf('Failed to parse WKT string: %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * Normalizes WKT string by extracting first element from GEOMETRYCOLLECTION if needed.
     *
     * GEOMETRYCOLLECTION is not supported by longitude-one/doctrine-spatial library,
     * so we extract the first geometry element from the collection.
     *
     * @param string $wkt The WKT string to normalize
     *
     * @return string The normalized WKT string
     */
    private function normalizeWkt(string $wkt): string
    {
        $wkt = trim($wkt);

        // Si c'est un GEOMETRYCOLLECTION, extraire le premier élément
        if (preg_match('/^GEOMETRYCOLLECTION\s*\(/is', $wkt)) {
            // Retirer le préfixe GEOMETRYCOLLECTION( et le ) final
            $wkt = preg_replace('/^GEOMETRYCOLLECTION\s*\(\s*/is', '', $wkt);
            $wkt = preg_replace('/\s*\)\s*$/s', '', $wkt);

            // Extraire le premier élément géométrique en comptant les parenthèses
            $depth = 0;
            $firstElementEnd = 0;
            for ($i = 0; $i < strlen($wkt); ++$i) {
                if ('(' === $wkt[$i]) {
                    ++$depth;
                } elseif (')' === $wkt[$i]) {
                    --$depth;
                    if (0 === $depth) {
                        $firstElementEnd = $i + 1;
                        break;
                    }
                }
            }
            if ($firstElementEnd > 0) {
                $wkt = trim(substr($wkt, 0, $firstElementEnd));
            }
        }

        return $wkt;
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
