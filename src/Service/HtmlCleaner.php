<?php

namespace App\Service;

class HtmlCleaner
{
    public static function clean(string $html): string
    {
        return strip_tags(html_entity_decode($html));
    }

    public static function cleanFrontEndEntry(string $html): string
    {
        return nl2br(htmlspecialchars($html, \ENT_QUOTES, 'UTF-8'));
    }
}
