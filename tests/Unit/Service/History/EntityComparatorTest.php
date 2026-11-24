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
    public function testProcessValueWithPrimitive(): void
    {
        $value = 'test';
        $result = $this->entityComparator->processValue($value);

        $this->assertSame($value, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testProcessValueWithObjectHavingGetId(): void
    {
        $stub = $this->createStub(EntityHistoryInterface::class);
        $stub->method('getId')->willReturn(123);

        $result = $this->entityComparator->processValue($stub);

        $this->assertSame(123, $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testProcessValueWithObjectHavingToArray(): void
    {
        $stub = $this->createStub(SituationFoyer::class);
        $stub->method('toArray')->willReturn(['key' => 'value']);

        $result = $this->entityComparator->processValue($stub);

        $this->assertSame(['key' => 'value'], $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testProcessValueWithDateTime(): void
    {
        $date = new \DateTime('2023-01-01 12:34:56');
        $result = $this->entityComparator->processValue($date);

        $this->assertSame('2023-01-01 12:34:56', $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCompareValuesPrimitives(): void
    {
        $result = $this->entityComparator->compareValues('old', 'new', 'field');

        $this->assertSame(['old' => 'old', 'new' => 'new'], $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCompareValuesSamePrimitives(): void
    {
        $result = $this->entityComparator->compareValues('same', 'same', 'field');

        $this->assertSame([], $result);
    }

    /**
     * @throws \ReflectionException
     */
    public function testCompareValuesArrays(): void
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
    public function testCompareValuesIgnoredField(): void
    {
        $result = $this->entityComparator->compareValues('oldValue', 'newValue', 'password');

        $this->assertSame(
            [
                'old' => 'oldV..........',
                'new' => 'newV..........',
            ], $result
        );
    }

    /**
     * @throws \ReflectionException
     */
    public function testCompareValuesMasksAuthCode(): void
    {
        $this->assertSame(
            ['old' => null, 'new' => '***'],
            $this->entityComparator->compareValues(null, '123456', 'authCode')
        );
        $this->assertSame(
            ['old' => '***', 'new' => null],
            $this->entityComparator->compareValues('123456', null, 'authCode')
        );
    }

    public function testGetEntityPropertiesMasksSensitiveFields(): void
    {
        $entity = new class {
            public string $email = 'agent@example.com';
            public string $password = 'hashedpassword';
            public string $authCode = '123456';
        };

        $properties = $this->entityComparator->getEntityPropertiesAndValueNormalized($entity);

        $this->assertSame('***', $properties['authCode']);
        $this->assertSame('hash***', $properties['password']);
        $this->assertSame('agent@example.com', $properties['email']);
    }
}
