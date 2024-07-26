<?php

namespace App\Tests\Unit\Validator;

use App\Validator\InseeToExclude;
use App\Validator\InseeToExcludeValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class InseeToExcludeValidatorTest extends ConstraintValidatorTestCase
{
    private const ERROR = 'La valeur "{{ value }}" n\'est pas valide. Elle doit être une liste de codes INSEE séparés par des virgules ou vide.';

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new InseeToExcludeValidator();
    }

    /**
     * @dataProvider provideValues
     */
    public function testValues(array $insee, bool $isValid, ?string $message = null): void
    {
        $constraint = new InseeToExclude();
        $this->validator->validate($insee, $constraint);
        if ($isValid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($message)
                ->setParameter('{{ value }}', implode(',', $insee))
                ->assertRaised();
        }
    }

    public function provideValues(): \Generator
    {
        yield 'all' => [['all'], false, self::ERROR];
        yield '[44058]' => [[44058], true];
        yield '[44058,44890]' => [[44058, 44890], true];
        yield 'error' => [['error'], false, self::ERROR];
        yield 'on test des trucs, et des machins' => [['on test des trucs', 'et des machins'], false, self::ERROR];
        yield '[440589,44890]' => [[440589, 44890], false, self::ERROR];
    }
}
