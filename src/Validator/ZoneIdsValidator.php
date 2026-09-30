<?php

namespace App\Validator;

use App\Entity\AutoAffectationRule;
use App\Repository\ZoneRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ZoneIdsValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ZoneRepository $zoneRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ZoneIds) {
            throw new UnexpectedTypeException($constraint, ZoneIds::class);
        }
        if (null === $value || '' === $value) {
            return;
        }

        foreach ($value as $id) {
            if (!preg_match('/^\d+$/', mb_trim((string) $id))) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', implode(',', $value))
                    ->addViolation();

                return;
            }
        }

        $rule = $this->context->getObject();
        if (!$rule instanceof AutoAffectationRule || null === $rule->getTerritory()) {
            return;
        }

        foreach ($value as $id) {
            $zone = $this->zoneRepository->find((int) $id);
            if (null === $zone) {
                $this->context->buildViolation($constraint->messageNotFound)
                    ->setParameter('{{ id }}', (string) $id)
                    ->addViolation();
            } elseif ($zone->getTerritory()->getId() !== $rule->getTerritory()->getId()) {
                $this->context->buildViolation($constraint->messageWrongTerritory)
                    ->setParameter('{{ id }}', (string) $id)
                    ->setParameter('{{ territory }}', $rule->getTerritory()->getZipAndName())
                    ->addViolation();
            }
        }
    }
}
