<?php

namespace App\Utils;

use App\Entity\Enum\ProfileDeclarant;
use Symfony\Component\Validator\Constraints\NotBlank;

class AttributeParser
{
    public static function parse(
        string $class,
        string $field,
        string $constraint,
    ): ?array {
        $reflector = new \ReflectionClass($class);
        /** @var \ReflectionAttribute[] $attributes */
        $attributes = $reflector->getProperty($field)->getAttributes($constraint);

        return $attributes;
    }

    public static function showLabelAsFacultatif(
        string $class,
        string $field,
        ProfileDeclarant $profileDeclarant,
        bool $isNewForm = true
    ): string {
        $attributes = self::parse($class, $field, NotBlank::class);
        $groups = [];
        if (!empty($attributes)) {
            $groups = $attributes[0]->getArguments()['groups'] ?? [];
        }

        if (!$isNewForm) {
            if (empty($attributes) || !empty($groups)) {
                return '(facultatif)';
            }

            return '';
        }

        return !empty($groups) && !\in_array($profileDeclarant->value, $groups) ? '(facultatif)' : '';
    }
}
