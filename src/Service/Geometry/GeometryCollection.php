<?php

namespace App\Service\Geometry;

use LongitudeOne\Spatial\PHP\Types\AbstractGeometry;
use LongitudeOne\Spatial\PHP\Types\Geometry\GeometryInterface;

/**
 * GeometryCollection object for the GEOMETRYCOLLECTION geometry type.
 * Contains a collection of multiple geometry objects.
 */
class GeometryCollection extends AbstractGeometry implements GeometryInterface
{
    /**
     * @var GeometryInterface[]
     */
    private array $geometries = [];

    /**
     * @param GeometryInterface[] $geometries Array of geometry objects
     * @param int|null            $srid       Spatial Reference System Identifier
     */
    public function __construct(array $geometries, ?int $srid = null)
    {
        $this->geometries = $geometries;
        $this->setSrid($srid);
    }

    /**
     * Get all geometries in this collection.
     *
     * @return GeometryInterface[]
     */
    public function getGeometries(): array
    {
        return $this->geometries;
    }

    /**
     * Get a geometry at a specific index.
     */
    public function getGeometry(int $index): ?GeometryInterface
    {
        return $this->geometries[$index] ?? null;
    }

    /**
     * Get the number of geometries in this collection.
     */
    public function count(): int
    {
        return count($this->geometries);
    }

    public function getType(): string
    {
        return 'GeometryCollection';
    }

    /**
     * Convert this geometry collection to an array.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->geometries as $geometry) {
            $result[] = [
                'type' => $geometry->getType(),
                'value' => $geometry->toArray(),
            ];
        }

        return $result;
    }

    public function __toString(): string
    {
        $parts = [];
        foreach ($this->geometries as $geometry) {
            $parts[] = strtoupper($geometry->getType()).'('.(string) $geometry.')';
        }

        return implode(',', $parts);
    }

    /**
     * Try to simplify the collection to a single geometry if possible.
     * - If collection has only one element, return that element
     * - If all elements are Polygons, return a MultiPolygon
     * - If collection contains Polygons + other types, extract only Polygons and return MultiPolygon
     * - Otherwise, return the first geometry found (fallback).
     */
    public function simplify(): GeometryInterface
    {
        if (1 === \count($this->geometries)) {
            return $this->geometries[0];
        }

        // Extract all polygons from the collection
        $polygons = [];
        foreach ($this->geometries as $geometry) {
            if ('Polygon' === $geometry->getType()) {
                $polygons[] = $geometry;
            }
        }

        // If we have polygons, convert to MultiPolygon
        if (!empty($polygons)) {
            if (1 === \count($polygons)) {
                // Single polygon, return it directly
                return $polygons[0];
            }

            // Multiple polygons, create MultiPolygon
            /** @var \LongitudeOne\Spatial\PHP\Types\Geometry\Polygon[] $polygons */
            $multiPolygonClass = 'LongitudeOne\\Spatial\\PHP\\Types\\Geometry\\MultiPolygon';

            return new $multiPolygonClass($polygons, $this->srid);
        }

        // No polygons found, return the first geometry as fallback
        return $this->geometries[0];
    }
}
