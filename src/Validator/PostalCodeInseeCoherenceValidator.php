<?php

namespace App\Validator;

use App\Dto\Request\Signalement\SignalementDraftRequest;
use App\Entity\Commune;
use App\Service\Signalement\PostalCodeHomeChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class PostalCodeInseeCoherenceValidator extends ConstraintValidator
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PostalCodeHomeChecker $postalCodeHomeChecker,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$value instanceof SignalementDraftRequest) {
            throw new UnexpectedValueException($value, SignalementDraftRequest::class);
        }

        if (!$constraint instanceof PostalCodeInseeCoherence) {
            throw new UnexpectedValueException($constraint, PostalCodeInseeCoherence::class);
        }

        $postalCode = $value->getAdresseLogementAdresseDetailCodePostal();
        $inseeCode = $value->getAdresseLogementAdresseDetailInsee();
        $inseeCode = $this->postalCodeHomeChecker->normalizeInseeCode($postalCode, $inseeCode);
        if (!$postalCode || !$inseeCode) {
            return;
        }

        $commune = $this->em->getRepository(Commune::class)->findOneBy([
            'codePostal' => $postalCode,
            'codeInsee' => $inseeCode,
        ]);

        if (!$commune) {
            \Sentry\captureMessage(sprintf(
                'Incohérence code postal et code INSEE : Code postal "%s", Code INSEE "%s"',
                $postalCode,
                $inseeCode
            ));

            $message = sprintf(
                'Le code postal %s et le code INSEE %s ne sont pas cohérents.',
                $postalCode,
                $inseeCode
            );

            $this->context
                ->buildViolation($message)
                ->atPath('adresseLogementAdresseDetailCodePostal')
                ->addViolation();
        }
    }
}
