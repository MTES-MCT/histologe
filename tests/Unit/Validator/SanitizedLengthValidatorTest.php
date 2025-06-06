<?php

namespace App\Tests\Unit\Validator;

use App\Validator\SanitizedLength;
use App\Validator\SanitizedLengthValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<SanitizedLengthValidator>
 */
class SanitizedLengthValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): SanitizedLengthValidator
    {
        return new SanitizedLengthValidator();
    }

    /**
     * @dataProvider provideValidValues
     */
    public function testValueIsValid(mixed $value): void
    {
        $constraint = new SanitizedLength(10, 'Text too short.');
        $this->validator->validate($value, $constraint);
        $this->assertNoViolation();
    }

    /**
     * @dataProvider provideInvalidValues
     */
    public function testSanitizedTextTooShort(string $value): void
    {
        $constraint = new SanitizedLength(10, 'Text too short.');
        $this->validator->validate($value, $constraint);
        $this->buildViolation('Text too short.')
            ->setParameter('{{ limit }}', '10')
            ->assertRaised();
    }

    public function provideValidValues(): \Generator
    {
        yield 'null value' => [null];
        yield 'empty value' => [''];
        yield 'valid value' => ['<p>Lorem ipsum dolor sit amet</p>'];
    }

    public function provideInvalidValues(): \Generator
    {
        yield 'too short value with no html' => ['Hi buddy!'];
        yield 'too short value with html' => ['<b>Hi buddy!</b>'];
    }

    public function testNonStringThrowsException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $constraint = new SanitizedLength(10, 'Text too short.');
        $this->validator->validate(12345, $constraint);
    }
}
