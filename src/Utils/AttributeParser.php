<?php

namespace App\Utils;

use App\Entity\Enum\ProfileDeclarant;
use Symfony\Component\Validator\Constraints\NotBlank;

class AttributeParser
{
    public static function showLabelAsFacultatif(
        string $class,
        string $field,
        ?ProfileDeclarant $profileDeclarant = null
    ): string {
        $reflector = new \ReflectionClass($class);
        /** @var \ReflectionAttribute[] $attributes */
        $attributes = $reflector->getProperty($field)->getAttributes(NotBlank::class);
        if (null === $profileDeclarant) {
            return empty($attributes) ? '(facultatif)' : '';
        }

        $groups = [];
        if (!empty($attributes)) {
            $groups = $attributes[0]->getArguments()['groups'] ?? [];
        }

        return !empty($groups) && !\in_array($profileDeclarant->value, $groups) ? '(facultatif)' : '';
    }
}
