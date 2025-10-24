<?php

namespace App\Utils;

use App\Entity\Enum\ProfileDeclarant;
use Symfony\Component\Validator\Constraints\NotBlank;

class AttributeParser
{
    /**
     * @template T of object
     *
     * @param class-string     $class
     * @param non-empty-string $field
     * @param class-string<T>  $constraint
     *
     * @return list<\ReflectionAttribute<T>>
     */
    public static function parse(
        string $class,
        string $field,
        string $constraint,
    ): ?array {
        $reflector = new \ReflectionClass($class);

        return $reflector->getProperty($field)->getAttributes($constraint);
    }

    public static function showLabelAsFacultatif(
        string $class,
        string $field,
        ProfileDeclarant $profileDeclarant,
        bool $isNewForm = true,
    ): string {
    assert(class_exists($class));
    assert($field !== '');
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
