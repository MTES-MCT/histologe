<?php

namespace App\Service\History;

class EntityComparator
{
    /** @var string[] */
    private const array FIELDS_TO_TRUNCATE = [
        'password',
        'token',
        'idossToken',
        'esaboraToken',
        'area',
    ];

    /**
     * Champs dont la valeur ne doit jamais être stockée, même partiellement (codes à usage unique).
     * Seule la présence d'une valeur est conservée.
     *
     * @var string[]
     */
    private const array FIELDS_TO_MASK = [
        'authCode',
    ];

    private const string MASK = '***';

    /**
     * @throws \ReflectionException
     */
    public function processValue(mixed $value): mixed
    {
        if (is_object($value)) {
            $reflection = new \ReflectionClass($value);
            if ($reflection->hasMethod('getId')) {
                return $value->getId(); // @phpstan-ignore-line
            }
            if ($reflection->hasMethod('toArray')) {
                return $value->toArray(); // @phpstan-ignore-line
            }
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d H:i:s');
            }
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }

    /**
     * @return array<mixed>
     */
    public function compareValues(mixed $oldValue, mixed $newValue, string $field): array
    {
        $changes = [];
        if (is_array($oldValue) && is_array($newValue)) {
            foreach ($oldValue as $key => $oldSubValue) {
                if (array_key_exists($key, $newValue)) {
                    $subChanges = $this->compareValues($oldSubValue, $newValue[$key], $key);
                    if (!empty($subChanges)) {
                        $changes[$key] = $subChanges;
                    }
                }
            }

            return $changes;
        }

        $oldValue = $this->processValue($oldValue);
        $newValue = $this->processValue($newValue);

        if ($oldValue === $newValue) {
            return $changes;
        }

        return [
            'old' => $this->protectSensitiveValue($oldValue, $field),
            'new' => $this->protectSensitiveValue($newValue, $field),
        ];
    }

    private function protectSensitiveValue(mixed $value, string $field): mixed
    {
        if (in_array($field, self::FIELDS_TO_MASK, true)) {
            return null === $value ? null : self::MASK;
        }

        if (in_array($field, self::FIELDS_TO_TRUNCATE, true)) {
            return substr((string) $value, 0, 4).str_repeat('.', 10);
        }

        return $value;
    }

    /**
     * @param object $entity
     *
     * @return array<string, mixed>
     */
    public function getEntityPropertiesAndValueNormalized($entity): array
    {
        $reflection = new \ReflectionClass($entity);
        $properties = $reflection->getProperties();
        $propertyValues = [];

        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $this->processValue($property->getValue($entity));
            if ($value) {
                $propertyValues[$property->getName()] = $this->protectSensitiveValue($value, $property->getName());
            }
        }

        return $propertyValues;
    }
}
