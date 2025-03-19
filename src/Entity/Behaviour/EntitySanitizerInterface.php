<?php

namespace App\Entity\Behaviour;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

interface EntitySanitizerInterface
{
    public function sanitizeDescription(HtmlSanitizerInterface $htmlSanitizer): void;

    public function getDescription(): ?string;
}
