<?php

namespace App\Service;

class RequestDataExtractor
{
    /**
     * @param array<string, mixed> $requestData
     */
    public static function getString(array $requestData, string $key): ?string
    {
        return is_string($requestData[$key] ?? null) ? $requestData[$key] : null;
    }

    /**
     * @param array<string, mixed> $requestData
     *
     * @return array<mixed>
     */
    public static function getArray(array $requestData, string $key): array
    {
        return is_array($requestData[$key] ?? null) ? $requestData[$key] : [];
    }
}
