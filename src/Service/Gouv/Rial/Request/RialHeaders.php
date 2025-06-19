<?php

namespace App\Service\Gouv\Rial\Request;

use Symfony\Component\Uid\Uuid;

class RialHeaders
{
    /**
     * @return array<string, string>
     */
    public static function getGenerateTokenHeaders(string $rialKey, string $rialSecret): array
    {
        $keyEncoded = base64_encode($rialKey.':'.$rialSecret);

        return [
            'Authorization' => 'Basic '.$keyEncoded,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function getSearchLocauxHeaders(string $accessToken): array
    {
        $correlationId = Uuid::v4();

        return [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$accessToken,
            'X-Correlation-ID' => $correlationId,
        ];
    }
}
