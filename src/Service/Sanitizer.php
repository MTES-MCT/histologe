<?php

namespace App\Service;

use App\Entity\User;

class Sanitizer
{
    public static function sanitize($text): string
    {
        $textSanitized = preg_replace('/<p[^>]*>/', '', $text); // Remove the start <p> or <p attr="">

        return str_replace('</p>', '<br>', $textSanitized); // Replace the end
    }

    public static function tagArchivedEmail(string $email): string
    {
        return $email.User::SUFFIXE_ARCHIVED.(new \DateTimeImmutable())->format('YmdHi');
    }
}
