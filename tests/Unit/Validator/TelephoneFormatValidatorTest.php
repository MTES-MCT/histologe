<?php

namespace App\Tests\Unit\Validator;

use App\Validator\TelephoneFormat;
use App\Validator\TelephoneFormatValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<TelephoneFormatValidator>
 */
class TelephoneFormatValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): TelephoneFormatValidator
    {
        return new TelephoneFormatValidator();
    }

    /**
     * @dataProvider provideValidPhoneNumbers
     */
    public function testValidPhoneNumbers(mixed $phoneNumber): void
    {
        $constraint = new TelephoneFormat();
        $this->validator->validate($phoneNumber, $constraint);
        $this->assertNoViolation();
    }

    /**
     * @dataProvider provideInvalidPhoneNumbers
     */
    public function testInvalidPhoneNumbers(string $phoneNumber): void
    {
        $constraint = new TelephoneFormat();
        $this->validator->validate($phoneNumber, $constraint);
        $this->buildViolation('Le numéro de téléphone "{{ value }}" n\'est pas au bon format.')
            ->setParameter('{{ value }}', $phoneNumber)
            ->assertRaised();
    }

    public function provideValidPhoneNumbers(): \Generator
    {
        yield 'null value' => [null];
        yield 'empty value' => [''];

        // Formats français acceptés
        yield 'french format with 0' => ['0808080808'];
        yield 'french format with 0 and spaces' => ['08 08 08 08 08'];
        yield 'french format with 0 and dashes' => ['08-08-08-08-08'];
        yield 'french format with country code' => ['33808080808'];
        yield 'french format with + and country code' => ['+33808080808'];
        yield 'french format with + and spaces' => ['+33 8 08 08 08 08'];

        // Formats étrangers
        yield 'italian phone number' => ['+39 6 8888 1111'];
        yield 'italian phone number without spaces' => ['+3968888111'];
        yield 'belgian phone number' => ['+32 2 123 45 67'];
        yield 'swiss phone number' => ['+41 22 345 67 89'];
    }

    public function provideInvalidPhoneNumbers(): \Generator
    {
        yield 'phone with letter at end' => ['0808080808D'];
        yield 'phone with letters' => ['08ABCD0808'];
        yield 'phone with special chars' => ['080808080@'];
        yield 'incomplete phone number' => ['0808'];
        yield 'random text' => ['Non communiqué'];
        yield 'only letters' => ['ABCDEFGHIJ'];
    }
}
