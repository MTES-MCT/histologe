<?php

namespace App\Validator;

use App\Entity\User;
use App\Repository\PartnerRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UserPartnerEmailMultiValidator extends ConstraintValidator
{
    public function __construct(
        private readonly PartnerRepository $partnerRepository,
        private readonly UserRepository $userRepository,
        private readonly Security $security,
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

        if (!$constraint instanceof UserPartnerEmailMulti) {
            throw new UnexpectedValueException($constraint, UserPartnerEmailMulti::class);
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
        $userExist = $this->userRepository->findAgentByEmail($email);
        if (!$userExist) {
            return;
        }
        if ($userExist->isApiUser() && !$this->security->isGranted('ROLE_ADMIN')) {
            $this->context->buildViolation('Un utilisateur API existe déjà avec cette adresse e-mail.')->atPath('email')->addViolation();
        }
        if ($userExist->isTerritoryAdmin()) {
            $this->context->buildViolation('Un utilisateur Responsable Territoire existe déjà avec cette adresse e-mail.')->atPath('email')->addViolation();
        }
        if ($userExist->isSuperAdmin()) {
            $this->context->buildViolation('Un utilisateur Super Admin existe déjà avec cette adresse e-mail.')->atPath('email')->addViolation();
        }
        if ($userExist->hasPermissionAffectation()) {
            $this->context->buildViolation('Un utilisateur ayant les droits d\'affectation existe déjà avec cette adresse e-mail.')->atPath('email')->addViolation();
        }
        if ($userExist->hasPartnerInTerritory($user->getFirstTerritory())) {
            $this->context->buildViolation('Un utilisateur avec cette adresse e-mail existe déja sur le territoire.')->atPath('email')->addViolation();
        }
    }
}
