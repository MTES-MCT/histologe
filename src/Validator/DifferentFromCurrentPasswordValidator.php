<?php

namespace App\Validator;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class DifferentFromCurrentPasswordValidator extends ConstraintValidator
{
    public function __construct(
        private readonly UserPasswordHasherInterface $userPasswordHasher,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof DifferentFromCurrentPassword) {
            throw new UnexpectedTypeException($constraint, DifferentFromCurrentPassword::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $user = $this->context->getObject();
        if (!$user instanceof User) {
            return;
        }

        if (null === $user->getPassword() || '' === $user->getPassword()) {
            return;
        }

        if ($this->userPasswordHasher->isPasswordValid($user, (string) $value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
