<?php

namespace App\Validator;

use App\Entity\Partner;
use App\Repository\PartnerRepository;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueEmailPartnerByTerritoryValidator extends ConstraintValidator
{
    public function __construct(private readonly PartnerRepository $partnerRepository)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueEmailPartnerByTerritory) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\UniqueEmailPartnerByTerritory');
        }

        if (null === $value || '' === $value) {
            return;
        }

        /** @var Partner $value */
        $email = $value->getEmail();
        if (null === $email || '' === $email) {
            return;
        }

        $territory = $value->getTerritory();
        $existingEmailPartner = $this->partnerRepository->findBy(['email' => $email, 'territory' => $territory]);

        if (count($existingEmailPartner) > 0) {
            /* @var UniqueEmailPartnerByTerritory $constraint */
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ email }}', $email)
                ->setParameter('{{ territory }}', $territory)
                ->addViolation();
        }
    }
}
