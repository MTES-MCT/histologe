<?php

namespace App\Tests\Unit\Validator;

use App\Validator\InseeToInclude;
use App\Validator\InseeToIncludeValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class InseeToIncludeValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator(): ConstraintValidatorInterface
    {
        return new InseeToIncludeValidator();
    }

    /**
     * @dataProvider provideValues
     */
    public function testValues(string $insee, bool $isValid, ?string $message = null): void
    {
        $constraint = new InseeToInclude();
        $this->validator->validate($insee, $constraint);
        if ($isValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($message)
                ->setParameter('{{ value }}', $insee)
                ->assertRaised();
        }
    }

    public function provideValues(): \Generator
    {
        yield 'all' => ['all', true];
        yield 'partner_list' => ['partner_list', true];
        yield '44058' => ['44058', true];
        yield '44058,44890' => ['44058,44890', true];
        yield 'error' => ['error', false, 'La valeur "{{ value }}" n\'est pas valide. Elle doit être soit "all", "partner_list", soit une liste de codes INSEE séparés par des virgules.'];
        yield 'on test des trucs, et des machins' => ['on test des trucs, et des machins', false, 'La valeur "{{ value }}" n\'est pas valide. Elle doit être soit "all", "partner_list", soit une liste de codes INSEE séparés par des virgules.'];
        yield '440589,44890' => ['440589,44890', false, 'La valeur "{{ value }}" n\'est pas valide. Elle doit être soit "all", "partner_list", soit une liste de codes INSEE séparés par des virgules.'];
    }
}
