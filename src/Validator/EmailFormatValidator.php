<?php

namespace App\Validator;

use App\Command\FixEmailAddressesCommand;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validation;

class EmailFormatValidator
{
    public static function validate(mixed $value): bool
    {
        if (null === $value || '' === $value) {
            return false;
        }

        $emailConstraint = new Email(mode: Email::VALIDATION_MODE_STRICT);
        $validator = Validation::createValidator();
        $errors = $validator->validate(
            $value,
            $emailConstraint
        );

        $eguliasValidator = new EmailValidator();
        $rfcValidation = new RFCValidation();
        $emailValid = $eguliasValidator->isValid($value, $rfcValidation);

            $atPos = strrchr($value, '@');
    if ($atPos === false) {
        return false;
    }

    $domain = substr($atPos, 1);
        $domainParts = explode('.', $domain);
        $extension = end($domainParts);

        if (0 == $errors->count() && $emailValid && \strlen($extension) >= 2) {
            return true;
        }

        return false;
    }

    public static function isInvalidEmail(?string $email): bool
    {
        return !self::validate($email) || FixEmailAddressesCommand::EMAIL_HISTOLOGE_INCONNU === $email;
    }
}
