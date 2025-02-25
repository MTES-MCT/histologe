<?php

namespace App\Validator;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

trait DateNaissanceValidatorTrait
{
    public function validateDateNaissance(?string $dateNaissance, string $fieldName, ExecutionContextInterface $context): void
    {
        if ($dateNaissance) {
            $date = \DateTime::createFromFormat('Y-m-d', $dateNaissance);

            if (!$date) {
                $context->buildViolation('Format de date invalide (attendu : YYYY-MM-DD).')
                    ->atPath($fieldName)
                    ->addViolation();

                return;
            }

            if ($date > new \DateTime('today')) {
                $context->buildViolation('La date de naissance doit être dans le passé.')
                    ->atPath($fieldName)
                    ->addViolation();
            }
        }
    }
}
