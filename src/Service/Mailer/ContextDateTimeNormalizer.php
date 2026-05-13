<?php

namespace App\Service\Mailer;

/**
 * Service to normalize context arrays by converting serialized DateTime arrays back to DateTime objects.
 *
 * When DateTime objects are stored in JSON (e.g., in database), they are serialized as arrays with keys:
 * - date: the datetime string
 * - timezone: the timezone string
 * - timezone_type: the timezone type (usually 3)
 *
 * This service recursively traverses context arrays and converts these serialized DateTime arrays
 * back into proper DateTime objects.
 */
class ContextDateTimeNormalizer
{
    /**
     * Normalizes a context array by converting serialized DateTime arrays back to DateTime objects.
     *
     * @param array<mixed> $context
     *
     * @return array<mixed>
     */
    public function normalize(array $context): array
    {
        foreach ($context as $key => $value) {
            if (\is_array($value)) {
                // Check if this is a serialized DateTime
                if ($this->isSerializedDateTime($value)) {
                    try {
                        $context[$key] = new \DateTime($value['date'], new \DateTimeZone($value['timezone']));
                    } catch (\Exception) {
                        // If conversion fails, recurse into the array
                        $context[$key] = $this->normalize($value);
                    }
                } else {
                    // Recurse into nested arrays
                    $context[$key] = $this->normalize($value);
                }
            }
        }

        return $context;
    }

    /**
     * Checks if an array represents a serialized DateTime object.
     */
    private function isSerializedDateTime(array $value): bool
    {
        return isset($value['date'], $value['timezone'])
            && \is_string($value['date'])
            && \is_string($value['timezone']);
    }
}
