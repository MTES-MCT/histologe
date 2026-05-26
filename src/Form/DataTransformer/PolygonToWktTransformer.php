<?php

namespace App\Form\DataTransformer;

use App\Service\Geometry\GeometryFactory;
use LongitudeOne\Spatial\PHP\Types\Geometry\GeometryInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * @implements DataTransformerInterface<GeometryInterface, string>
 */
class PolygonToWktTransformer implements DataTransformerInterface
{
    public function __construct(
        private readonly GeometryFactory $geometryFactory,
    ) {
    }

    /**
     * Transforms a Geometry object to a WKT string (for displaying in form).
     */
    public function transform(mixed $value): string
    {
        if (null === $value) {
            return '';
        }

        if (!$value instanceof GeometryInterface) {
            throw new TransformationFailedException('Expected a Geometry object.');
        }

        return $this->geometryFactory->toWkt($value);
    }

    /**
     * Transforms a WKT string to a Geometry object (Polygon/MultiPolygon, etc.).
     */
    public function reverseTransform(mixed $value): ?GeometryInterface
    {
        if (empty($value)) {
            return null;
        }

        if (!is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        try {
            return $this->geometryFactory->createFromWkt($value);
        } catch (\InvalidArgumentException $e) {
            throw new TransformationFailedException($e->getMessage(), 0, $e);
        }
    }
}
