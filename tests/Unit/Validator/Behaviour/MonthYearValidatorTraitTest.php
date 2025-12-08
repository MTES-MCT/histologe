<?php

namespace App\Tests\Unit\Validator\Behaviour;

use App\Validator\Behaviour\MonthYearValidatorTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class MonthYearValidatorTraitTest extends TestCase
{
    /** @var MockObject&ExecutionContextInterface */
    private ExecutionContextInterface $context;
    private MonthYearValidatorImplementation $validator;

    protected function setUp(): void
    {
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator = new MonthYearValidatorImplementation();
    }

    public function testValidateMonthYearWithNullValue(): void
    {
        $this->context->expects($this->never())->method('buildViolation');

        $this->validator->validateMonthYear(null, 'dateField', $this->context);
    }

    public function testValidateMonthYearWithInvalidFormat(): void
    {
        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with('Format de date invalide (mm/aaaa).')
            ->willReturn($violationBuilder);

        $violationBuilder
            ->expects($this->once())
            ->method('atPath')
            ->with('dateField')
            ->willReturnSelf();

        $violationBuilder
            ->expects($this->once())
            ->method('addViolation');

        $this->validator->validateMonthYear('13/2023', 'dateField', $this->context);
    }

    public function testValidateMonthYearWithFutureDate(): void
    {
        $futureDate = (new \DateTimeImmutable('+1 month'))->format('m/Y');

        $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);

        $this->context
            ->expects($this->once())
            ->method('buildViolation')
            ->with('La date ne doit pas être dans le futur.')
            ->willReturn($violationBuilder);

        $violationBuilder
            ->expects($this->once())
            ->method('atPath')
            ->with('dateField')
            ->willReturnSelf();

        $violationBuilder
            ->expects($this->once())
            ->method('addViolation');

        $this->validator->validateMonthYear($futureDate, 'dateField', $this->context);
    }

    public function testValidateMonthYearWithValidPastDate(): void
    {
        $this->context->expects($this->never())->method('buildViolation');
        $this->validator->validateMonthYear('01/2020', 'dateField', $this->context);
    }
}

class MonthYearValidatorImplementation
{
    use MonthYearValidatorTrait;
}
