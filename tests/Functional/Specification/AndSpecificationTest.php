<?php

namespace App\Tests\Functional\Specification;

use App\Specification\AndSpecification;
use App\Specification\SpecificationInterface;
use PHPUnit\Framework\TestCase;

class AndSpecificationTest extends TestCase
{
    public function testAllSpecificationsSatisfied()
    {
        $spec1 = $this->createMock(SpecificationInterface::class);
        $spec1->method('isSatisfiedBy')->willReturn(true);

        $spec2 = $this->createMock(SpecificationInterface::class);
        $spec2->method('isSatisfiedBy')->willReturn(true);

        $spec3 = $this->createMock(SpecificationInterface::class);
        $spec3->method('isSatisfiedBy')->willReturn(true);

        $spec4 = $this->createMock(SpecificationInterface::class);
        $spec4->method('isSatisfiedBy')->willReturn(true);

        $andSpec = new AndSpecification($spec1, $spec2, $spec3, $spec4);

        $this->assertTrue($andSpec->isSatisfiedBy([]));
    }

    public function testOneSpecificationNotSatisfied()
    {
        $spec1 = $this->createMock(SpecificationInterface::class);
        $spec1->method('isSatisfiedBy')->willReturn(true);

        $spec2 = $this->createMock(SpecificationInterface::class);
        $spec2->method('isSatisfiedBy')->willReturn(false);

        $spec3 = $this->createMock(SpecificationInterface::class);
        $spec3->method('isSatisfiedBy')->willReturn(true);

        $spec4 = $this->createMock(SpecificationInterface::class);
        $spec4->method('isSatisfiedBy')->willReturn(true);

        $andSpec = new AndSpecification($spec1, $spec2, $spec3, $spec4);

        $this->assertFalse($andSpec->isSatisfiedBy([]));
    }

    public function testBothSpecificationNotSatisfied()
    {
        $spec1 = $this->createMock(SpecificationInterface::class);
        $spec1->method('isSatisfiedBy')->willReturn(false);

        $spec2 = $this->createMock(SpecificationInterface::class);
        $spec2->method('isSatisfiedBy')->willReturn(false);

        $andSpec = new AndSpecification($spec1, $spec2);

        $this->assertFalse($andSpec->isSatisfiedBy([]));
    }

    public function testNoSpecifications()
    {
        $andSpec = new AndSpecification();

        $this->assertTrue($andSpec->isSatisfiedBy([]));
    }
}
