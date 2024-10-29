<?php

namespace App\Service\History;

class EntityComparator
{
    private const array FIELDS_TO_TRUNCATE = [
        'password',
        'token',
        'idossToken',
        'esaboraToken',
        'area',
    ];

    /**
     * @throws \ReflectionException
     */
    public function processValue($value): mixed
    {
        if (is_object($value)) {
            $reflection = new \ReflectionClass($value);
            if ($reflection->hasMethod('getId')) {
                return $value->getId();
            }
            if ($reflection->hasMethod('toArray')) {
                return $value->toArray();
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
     * @throws \ReflectionException
     */
    public function compareValues($oldValue, $newValue, $field): array
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

        if (in_array($field, self::FIELDS_TO_TRUNCATE)) {
            return [
                'old' => substr((string) $oldValue, 0, 4).str_repeat('.', 10),
                'new' => substr((string) $newValue, 0, 4).str_repeat('.', 10),
            ];
        }

        return [
            'old' => $oldValue,
            'new' => $newValue,
        ];
    }

    /**
     * @throws \ReflectionException
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
                $propertyValues[$property->getName()] = $value;
            }
        }

        return $propertyValues;
    }
}
