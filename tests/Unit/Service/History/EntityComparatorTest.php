<?php

namespace App\Tests\Unit\Service\History;

use App\Entity\Behaviour\EntityHistoryInterface;
use App\Entity\Model\SituationFoyer;
use App\Service\History\EntityComparator;
use PHPUnit\Framework\TestCase;

class EntityComparatorTest extends TestCase
{
    private EntityComparator $entityComparator;

    protected function setUp(): void
    {
        $this->entityComparator = new EntityComparator();
    }

    /**
     * @throws \ReflectionException
     */
    public function testProcessValueWithPrimitive()
    {
        $value = 'test';
        $result = $this->entityComparator->processValue($value);

        $this->assertSame($value, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testProcessValueWithObjectHavingGetId()
    {
        $mock = $this->createMock(EntityHistoryInterface::class);
        $mock->method('getId')->willReturn(123);

        $result = $this->entityComparator->processValue($mock);

        $this->assertSame(123, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testProcessValueWithObjectHavingToArray()
    {
        $mock = $this->createMock(SituationFoyer::class);
        $mock->method('toArray')->willReturn(['key' => 'value']);

        $result = $this->entityComparator->processValue($mock);

        $this->assertSame(['key' => 'value'], $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testProcessValueWithDateTime()
    {
        $date = new \DateTime('2023-01-01 12:34:56');
        $result = $this->entityComparator->processValue($date);

        $this->assertSame('2023-01-01 12:34:56', $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCompareValuesPrimitives()
    {
        $result = $this->entityComparator->compareValues('old', 'new', 'field');

        $this->assertSame(['old' => 'old', 'new' => 'new'], $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCompareValuesSamePrimitives()
    {
        $result = $this->entityComparator->compareValues('same', 'same', 'field');

        $this->assertSame([], $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCompareValuesArrays()
    {
        $oldArray = ['key' => 'oldValue'];
        $newArray = ['key' => 'newValue'];
        $result = $this->entityComparator->compareValues($oldArray, $newArray, 'field');

        $expected = ['key' => ['old' => 'oldValue', 'new' => 'newValue']];
        $this->assertSame($expected, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCompareValuesIgnoredField()
    {
        $result = $this->entityComparator->compareValues('oldValue', 'newValue', 'password');

        $this->assertSame(
            [
                'old' => 'oldV..........',
                'new' => 'newV..........',
            ], $result
        );
    }
}
