<?php

namespace App\Validator;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Commune;
use App\Service\Signalement\PostalCodeHomeChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class IsTerritoryActiveValidator extends ConstraintValidator
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly PostalCodeHomeChecker $postalCodeHomeChecker)
    {
    }

    public function validate(mixed $obj, Constraint $constraint): void
    {
        if (!$obj instanceof SignalementDraftRequest) {
            throw new UnexpectedValueException($obj, SignalementDraftRequest::class);
        }

        if (!$constraint instanceof IsTerritoryActive) {
            throw new UnexpectedValueException($constraint, IsTerritoryActive::class);
        }

        $postalCode = $obj->getAdresseLogementAdresseDetailCodePostal();
        $inseeCode = $obj->getAdresseLogementAdresseDetailInsee();
        if ($postalCode && $inseeCode) {
            $commune = $this->em->getRepository(Commune::class)->findOneBy(['codePostal' => $postalCode, 'codeInsee' => $inseeCode]);
            if (!$commune) {
                $message = 'Le code postal "'.$postalCode.'" et le code INSEE "'.$inseeCode.'" ne sont pas cohérents.';
                $this->context
                    ->buildViolation($message)
                    ->atPath('adresseLogementAdresseDetailCodePostal')
                    ->addViolation();
            }

            if (!$this->postalCodeHomeChecker->isActiveByInseeCode($inseeCode)) {
                $message = 'Le territoire n\'est pas actif pour le code postal "'.$postalCode.'" et le code INSEE "'.$inseeCode.'".';
                $this->context
                    ->buildViolation($message)
                    ->atPath('adresseLogementAdresseDetailCodePostal')
                    ->addViolation();
            }
        }
    }
}
