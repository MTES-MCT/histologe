<?php

namespace App\Service\Gouv\ProConnect\Response;

use App\Service\Gouv\ProConnect\Model\JWK;

class JWKSResponse
{
    /**
     * @var JWK[]
     */
    private array $keys;

    public function __construct(string $data)
    {
        $keys = json_decode($data, true);
        $this->keys = array_map(fn ($key) => new JWK($key), $keys['keys']);
    }

    /**
     * @return JWK[]
     */
    public function getKeys(): array
    {
        return $this->keys;
    }

    public function findPublicKey(string $algorithm = 'RS256', string $keyType = 'RSA'): ?JWK
    {
        foreach ($this->keys as $key) {
            if ($key->alg === $algorithm && $key->kty === $keyType) {
                return $key;
            }
        }

        return null;
    }
}
