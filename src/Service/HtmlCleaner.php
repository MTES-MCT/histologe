<?php

namespace App\Service;

class HtmlCleaner
{
    public static function clean(string $html): string
    {
        return strip_tags(html_entity_decode($html));
    }
}
