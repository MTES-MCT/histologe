<?php

namespace App\Tests\Unit\Validator;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Validator\ValueLessThanOtherValue;
use App\Validator\ValueLessThanOtherValueValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<ValueLessThanOtherValueValidator>
 */
class ValueLessThanOtherValueValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new ValueLessThanOtherValueValidator();
    }

    public function testValidWhenValueLessThanOtherValue(): void
    {
        $dto = $this->createDto('2', '4');
        $constraint = new ValueLessThanOtherValue(
            property: 'compositionLogementNombreEnfants',
            otherProperty: 'compositionLogementNombrePersonnes',
        );
        $this->validator->validate($dto, $constraint);
        $this->assertNoViolation();
    }

    public function testValidWhenChildrenEqualPeople(): void
    {
        $dto = $this->createDto('3', '3');
        $constraint = new ValueLessThanOtherValue(
            property: 'compositionLogementNombreEnfants',
            otherProperty: 'compositionLogementNombrePersonnes',
        );
        $this->validator->validate($dto, $constraint);
        $this->assertNoViolation();
    }

    public function testInvalidWhenChildrenGreaterThanPeople(): void
    {
        $dto = $this->createDto('5', '3');
        $constraint = new ValueLessThanOtherValue(
            property: 'compositionLogementNombreEnfants',
            otherProperty: 'compositionLogementNombrePersonnes',
        );
        $this->validator->validate($dto, $constraint);
        $this->buildViolation($constraint->message)
            ->setParameter('{{ property }}', 'compositionLogementNombreEnfants')
            ->setParameter('{{ value }}', '5')
            ->setParameter('{{ otherProperty }}', 'compositionLogementNombrePersonnes')
            ->setParameter('{{ otherValue }}', '3')
            ->atPath('property.path.compositionLogementNombreEnfants')
            ->assertRaised();
    }

    public function testNoViolationIfNullValues(): void
    {
        $dto = $this->createDto(null, '3');
        $constraint = new ValueLessThanOtherValue(
            property: 'compositionLogementNombreEnfants',
            otherProperty: 'compositionLogementNombrePersonnes',
        );
        $this->validator->validate($dto, $constraint);
        $this->assertNoViolation();

        $dto = $this->createDto('2', null);
        $this->validator->validate($dto, $constraint);
        $this->assertNoViolation();
    }

    /**
     * @return SignalementDraftRequest|MockObject
     */
    private function createDto(?string $children, ?string $people)
    {
        $dto = $this->getMockBuilder(SignalementDraftRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCompositionLogementNombreEnfants', 'getCompositionLogementNombrePersonnes'])
            ->getMock();
        $dto->method('getCompositionLogementNombreEnfants')->willReturn($children);
        $dto->method('getCompositionLogementNombrePersonnes')->willReturn($people);

        return $dto;
    }
}
