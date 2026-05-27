<?php

namespace App\Tests\Unit\Service\Geometry;

use App\Service\Geometry\GeometryFactory;
use LongitudeOne\Spatial\PHP\Types\Geometry\LineString;
use LongitudeOne\Spatial\PHP\Types\Geometry\MultiPolygon;
use LongitudeOne\Spatial\PHP\Types\Geometry\Point;
use LongitudeOne\Spatial\PHP\Types\Geometry\Polygon;
use PHPUnit\Framework\TestCase;

class GeometryFactoryTest extends TestCase
{
    private GeometryFactory $geometryFactory;

    protected function setUp(): void
    {
        $this->geometryFactory = new GeometryFactory();
    }

    public function testCreateFromWktWithSimplePolygon(): void
    {
        $wkt = 'POLYGON((0 0, 0 10, 10 10, 10 0, 0 0))';

        $geometry = $this->geometryFactory->createFromWkt($wkt);

        $this->assertInstanceOf(Polygon::class, $geometry);
        $this->assertSame('Polygon', $geometry->getType());
    }

    public function testCreateFromWktWithPoint(): void
    {
        $wkt = 'POINT(2.5 48.8)';

        $geometry = $this->geometryFactory->createFromWkt($wkt);

        $this->assertInstanceOf(Point::class, $geometry);
        $this->assertSame('Point', $geometry->getType());
    }

    public function testCreateFromWktWithLineString(): void
    {
        $wkt = 'LINESTRING(0 0, 1 1, 2 2)';

        $geometry = $this->geometryFactory->createFromWkt($wkt);

        $this->assertInstanceOf(LineString::class, $geometry);
        $this->assertSame('LineString', $geometry->getType());
    }

    public function testCreateFromWktWithMultiPolygon(): void
    {
        $wkt = 'MULTIPOLYGON(((0 0, 0 10, 10 10, 10 0, 0 0)), ((20 20, 20 30, 30 30, 30 20, 20 20)))';

        $geometry = $this->geometryFactory->createFromWkt($wkt);

        $this->assertInstanceOf(MultiPolygon::class, $geometry);
        $this->assertSame('MultiPolygon', $geometry->getType());
    }

    public function testCreateFromWktWithGeometryCollectionExtractsFirstElement(): void
    {
        $wkt = 'GEOMETRYCOLLECTION(POLYGON((0 0, 0 10, 10 10, 10 0, 0 0)))';

        $geometry = $this->geometryFactory->createFromWkt($wkt);

        $this->assertInstanceOf(Polygon::class, $geometry);
        $this->assertSame('Polygon', $geometry->getType());
    }

    public function testCreateFromWktWithGeometryCollectionAndSpaces(): void
    {
        $wkt = '  GEOMETRYCOLLECTION ( POLYGON((0 0, 0 10, 10 10, 10 0, 0 0)) )  ';

        $geometry = $this->geometryFactory->createFromWkt($wkt);

        $this->assertInstanceOf(Polygon::class, $geometry);
        $this->assertSame('Polygon', $geometry->getType());
    }

    public function testCreateFromWktWithGeometryCollectionContainingMultipleElements(): void
    {
        $wkt = 'GEOMETRYCOLLECTION(POLYGON((0 0, 0 10, 10 10, 10 0, 0 0)), POINT(5 5))';

        $geometry = $this->geometryFactory->createFromWkt($wkt);

        // Should extract only the first element (Polygon)
        $this->assertInstanceOf(Polygon::class, $geometry);
        $this->assertSame('Polygon', $geometry->getType());
    }

    public function testCreateFromWktWithComplexPolygonCoordinates(): void
    {
        $wkt = 'POLYGON ((0.366388704693417 43.67718188603829, 0.372057962211398 43.676226950260016, 0.366388704693417 43.67718188603829))';

        $geometry = $this->geometryFactory->createFromWkt($wkt);

        $this->assertInstanceOf(Polygon::class, $geometry);
    }

    public function testCreateFromWktWithInvalidWktThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to parse WKT string');

        $this->geometryFactory->createFromWkt('INVALID WKT');
    }

    public function testToWktConvertsPolygonToWktString(): void
    {
        $wkt = 'POLYGON((0 0, 0 10, 10 10, 10 0, 0 0))';
        $geometry = $this->geometryFactory->createFromWkt($wkt);

        $result = $this->geometryFactory->toWkt($geometry);

        $this->assertStringStartsWith('POLYGON', $result);
        $this->assertStringContainsString('0 0', $result);
        $this->assertStringContainsString('10 10', $result);
    }

    public function testToWktConvertsPointToWktString(): void
    {
        $wkt = 'POINT(2.5 48.8)';
        $geometry = $this->geometryFactory->createFromWkt($wkt);

        $result = $this->geometryFactory->toWkt($geometry);

        $this->assertStringStartsWith('POINT', $result);
        $this->assertStringContainsString('2.5', $result);
        $this->assertStringContainsString('48.8', $result);
    }
}
