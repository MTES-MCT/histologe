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

    public function testCreateFromWktWithGeometryCollectionWithSingleElement(): void
    {
        $wkt = 'GEOMETRYCOLLECTION(POLYGON((0 0, 0 10, 10 10, 10 0, 0 0)))';

        $geometry = $this->geometryFactory->createFromWkt($wkt);

        // Single element collection is simplified to the element itself
        $this->assertInstanceOf(Polygon::class, $geometry);
        $this->assertSame('Polygon', $geometry->getType());
    }

    public function testCreateFromWktWithGeometryCollectionAndSpaces(): void
    {
        $wkt = '  GEOMETRYCOLLECTION ( POLYGON((0 0, 0 10, 10 10, 10 0, 0 0)) )  ';

        $geometry = $this->geometryFactory->createFromWkt($wkt);

        // Single element collection is simplified to the element itself
        $this->assertInstanceOf(Polygon::class, $geometry);
        $this->assertSame('Polygon', $geometry->getType());
    }

    public function testCreateFromWktWithGeometryCollectionContainingMultiplePolygons(): void
    {
        $wkt = 'GEOMETRYCOLLECTION(POLYGON((0 0, 0 10, 10 10, 10 0, 0 0)), POLYGON((20 20, 20 30, 30 30, 30 20, 20 20)))';

        $geometry = $this->geometryFactory->createFromWkt($wkt);

        // Multiple polygons are simplified to MultiPolygon
        $this->assertInstanceOf(MultiPolygon::class, $geometry);
        $this->assertSame('MultiPolygon', $geometry->getType());
    }

    public function testCreateFromWktWithGeometryCollectionContainingMixedElements(): void
    {
        $wkt = 'GEOMETRYCOLLECTION(POLYGON((0 0, 0 10, 10 10, 10 0, 0 0)), POINT(5 5))';

        $geometry = $this->geometryFactory->createFromWkt($wkt);

        // Mixed elements: extract only Polygons, ignore Points
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

    public function testCreateFromWktWithRealWorldGeometryCollection(): void
    {
        $wkt = 'GEOMETRYCOLLECTION (POLYGON ((3.870621 43.623252, 3.855515 43.619027, 3.852081 43.61505, 3.850708 43.611322, 3.851395 43.604361, 3.858948 43.594914, 3.868561 43.591432, 3.89019 43.593422, 3.89431 43.59914, 3.90049 43.607096, 3.900833 43.61505, 3.888817 43.62375, 3.870621 43.623252)), POLYGON ((3.793373 43.62027, 3.78891 43.609582, 3.791656 43.606102, 3.804016 43.602124, 3.820839 43.601378, 3.828392 43.613808, 3.819466 43.622755, 3.793373 43.62027)))';

        $geometry = $this->geometryFactory->createFromWkt($wkt);

        // Should be converted to MultiPolygon
        $this->assertInstanceOf(MultiPolygon::class, $geometry);
        $this->assertSame('MultiPolygon', $geometry->getType());
    }
}
