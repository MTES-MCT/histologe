<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

class TestExtension extends AbstractExtension
{
    public function getTests(): array
    {
        return [
            new TwigTest('numeric', static fn ($value) => is_numeric($value)),
            new TwigTest('instanceof', static fn ($value, string $class): bool => $value instanceof $class),
        ];
    }
}
