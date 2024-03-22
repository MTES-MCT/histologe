<?php

namespace App\Service;

class Sanitizer
{
    public static function sanitize($text): string
    {
        $textSanitized = preg_replace('/<p[^>]*>/', '', $text); // Remove the start <p> or <p attr="">

        return str_replace('</p>', '<br>', $textSanitized); // Replace the end
    }

    public static function tagArchivedEmail(string $email): string
    {
        return $email.'.archived@'.(new \DateTimeImmutable())->format('YmdHi');
    }
}
