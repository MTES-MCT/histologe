<?php

namespace App\Validator;

use App\Entity\User;
use App\Repository\PartnerRepository;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UserPartnerEmailValidator extends ConstraintValidator
{
    public function __construct(
        private readonly PartnerRepository $partnerRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * @param User $user
     */
    public function validate($user, Constraint $constraint): void
    {
        if (!$user instanceof User) {
            throw new UnexpectedValueException($user, User::class);
        }

        if (!$constraint instanceof UserPartnerEmail) {
            throw new UnexpectedValueException($constraint, UserPartnerEmail::class);
        }

        $email = $user->getEmail();
        if (!EmailFormatValidator::validate($email)) {
            $this->context->buildViolation('L\'adresse e-mail est invalide.')->atPath('email')->addViolation();

            return;
        }
        $partnerExist = $this->partnerRepository->findOneBy(['email' => $email]);
        if ($partnerExist) {
            $this->context->buildViolation('Un partenaire existe déjà avec cette adresse e-mail.')->atPath('email')->addViolation();
        }
        $userExist = $this->userRepository->findOneBy(['email' => $email]);
        if ($userExist && $userExist->getId() !== $user->getId()) {
            $this->context->buildViolation('Un utilisateur existe déjà avec cette adresse e-mail.')->atPath('email')->addViolation();
        }
    }
}
