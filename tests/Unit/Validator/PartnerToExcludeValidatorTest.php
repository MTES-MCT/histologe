<?php

namespace App\Tests\Unit\Validator;

use App\Validator\PartnerToExclude;
use App\Validator\PartnerToExcludeValidator;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class PartnerToExcludeValidatorTest extends ConstraintValidatorTestCase
{
    private const ERROR = 'La valeur "{{ value }}" n\'est pas valide. Elle doit être une liste d\'Id partenaires séparés par des virgules ou vide.';

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new PartnerToExcludeValidator();
    }

    /**
     * @dataProvider provideValues
     */
    public function testValues(array $insee, bool $isValid, ?string $message = null): void
    {
        $constraint = new PartnerToExclude();
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
        yield '[2]' => [[2], true];
        yield '[22]' => [[22], true];
        yield '[22,333]' => [[22, 333], true];
        yield '[440589,44890]' => [[440589, 44890], true];
        yield 'error' => [['error'], false, self::ERROR];
        yield 'on test des trucs, et des machins' => [['on test des trucs', 'et des machins'], false, self::ERROR];
    }
}
