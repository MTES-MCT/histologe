<?php

namespace App\Validator;

use App\Entity\AutoAffectationRule;
use App\Repository\PartnerRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PartnerToExcludeValidator extends ConstraintValidator
{
    public function __construct(
        private readonly PartnerRepository $partnerRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof PartnerToExclude) {
            throw new UnexpectedTypeException($constraint, PartnerToExclude::class);
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
        if (!$rule instanceof AutoAffectationRule) {
            return;
        }

        foreach ($value as $id) {
            $partner = $this->partnerRepository->find((int) $id);
            if (null === $partner) {
                $this->context->buildViolation($constraint->messageNotFound)
                    ->setParameter('{{ id }}', (string) $id)
                    ->addViolation();
            } elseif ($partner->getIsArchive()) {
                $this->context->buildViolation($constraint->messageArchived)
                    ->setParameter('{{ id }}', (string) $id)
                    ->addViolation();
            } elseif ($partner->getTerritory()?->getId() !== $rule->getTerritory()->getId()) {
                $this->context->buildViolation($constraint->messageWrongTerritory)
                    ->setParameter('{{ id }}', (string) $id)
                    ->setParameter('{{ territory }}', $rule->getTerritory()->getZipAndName())
                    ->addViolation();
            } elseif ($partner->getType() !== $rule->getPartnerType()) {
                $this->context->buildViolation($constraint->messageWrongType)
                    ->setParameter('{{ id }}', (string) $id)
                    ->setParameter('{{ type }}', $rule->getPartnerType()->label())
                    ->addViolation();
            }
        }
    }
}
