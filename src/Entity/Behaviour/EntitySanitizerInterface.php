<?php

namespace App\Entity\Behaviour;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

interface EntitySanitizerInterface
{
    public function sanitize(HtmlSanitizerInterface $htmlSanitizer): void;

    public function getDescription(): ?string;
}
