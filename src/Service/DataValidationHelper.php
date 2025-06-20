<?php

namespace App\Service;

use App\Command\FixEmailAddressesCommand;
use App\Validator\EmailFormatValidator;

class DataValidationHelper
{
    public static function isInvalidEmail(?string $email): bool
    {
        return !EmailFormatValidator::validate($email) || FixEmailAddressesCommand::EMAIL_HISTOLOGE_INCONNU === $email;
    }
}
