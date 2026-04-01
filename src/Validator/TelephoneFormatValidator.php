<?php

namespace App\Validator;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class TelephoneFormatValidator extends ConstraintValidator
{
    public function __construct()
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof TelephoneFormat) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\TelephoneFormat');
        }

        /* @var TelephoneFormat $constraint */

        if (null === $value || '' === $value) {
            return;
        }

        try {
            $phoneNumberUtil = PhoneNumberUtil::getInstance();
            $phoneNumberParsed = $phoneNumberUtil->parse($value, 'FR');

            // Vérifications pour numéro français :
            // la valeur saisie correspond exactement au numéro parsé (contrôle pour éviter que des caractères indésirables soient ignorés)
            $normalizedInput = preg_replace('/[\s\-\(\)\.]+/', '', $value);
            $formattedE164 = $phoneNumberUtil->format($phoneNumberParsed, \libphonenumber\PhoneNumberFormat::E164);

            // Accepter les formats: +33808080808, +330808080808, 33808080808, 0808080808
            // Rejeter: 0808080808D ou tout format avec caractères invalides
            $validFormats = [
                $formattedE164,                           // +33808080808
                ltrim($formattedE164, '+'),              // 33808080808
                '0'.substr(ltrim($formattedE164, '+'), 2), // 0808080808
            ];

            // Ajouter le format +330808080808 si numéro français (formulaire front)
            if (str_starts_with($formattedE164, '+33')) {
                $validFormats[] = '+330'.substr(ltrim($formattedE164, '+'), 2);
            }

            if (!in_array($normalizedInput, $validFormats, true)) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $value)
                    ->addViolation();

                return;
            }

            $isPossible = $phoneNumberUtil->isPossibleNumber($phoneNumberParsed);
            if (!$isPossible) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $value)
                    ->addViolation();
            }
        } catch (NumberParseException $e) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
