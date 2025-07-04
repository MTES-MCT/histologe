<?php

namespace App\Tests\Unit\Validator;

use App\Validator\InseeToInclude;
use App\Validator\InseeToIncludeValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<InseeToIncludeValidator>
 */
class InseeToIncludeValidatorTest extends ConstraintValidatorTestCase
{
    private const ERROR = 'La valeur "{{ value }}" n\'est pas valide. Elle doit être soit vide soit une liste de codes INSEE séparés par des virgules.';

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new InseeToIncludeValidator();
    }

    /**
     * @dataProvider provideValues
     */
    public function testValues(?string $insee, bool $isValid, ?string $message = null): void
    {
        $constraint = new InseeToInclude();
        $this->validator->validate($insee, $constraint);
        if ($isValid) {
            $this->assertNoViolation();
        } else {
            if (null === $insee) {
                $insee = 'null';
            }
            $this->buildViolation($message)
                ->setParameter('{{ value }}', $insee)
                ->assertRaised();
        }
    }

    public function provideValues(): \Generator
    {
        yield 'null' => [null, false, self::ERROR];
        yield 'empty' => ['', true];
        yield 'all' => ['all', false, self::ERROR];
        yield 'partner_list' => ['partner_list', false, self::ERROR];
        yield '44058' => ['44058', true];
        yield '44058,44890' => ['44058,44890', true];
        yield 'error' => ['error', false, self::ERROR];
        yield 'on test des trucs, et des machins' => ['on test des trucs, et des machins', false, self::ERROR];
        yield '440589,44890' => ['440589,44890', false, self::ERROR];
    }
}
