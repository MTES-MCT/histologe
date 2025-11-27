<?php

namespace App\Validator\Behaviour;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait MonthYearValidatorTrait
{
    public function validateMonthYear(?string $value, string $field, ExecutionContextInterface $context): void
    {
        if (null === $value) {
            return;
        }

        $date = \DateTimeImmutable::createFromFormat('!d/m/Y', '01/'.$value);
        $errors = \DateTimeImmutable::getLastErrors();
        if (false === $date || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            $context
                ->buildViolation('Format de date invalide (mm/aaaa).')
                ->atPath($field)
                ->addViolation();

            return;
        }

        if ($date >= new \DateTimeImmutable('today')) {
            $context
                ->buildViolation('La date ne doit pas être dans le futur.')
                ->atPath($field)
                ->addViolation();
        }
    }
}
