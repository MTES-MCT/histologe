<?php

namespace App\Service\Gouv\ProConnect\Model;

class JWK
{
    public ?string $kty = null;
    public ?string $alg = null;
    public ?string $use = null;
    public ?string $kid = null;
    public ?string $n = null;
    public ?string $e = null;

    public function __construct(array $data)
    {
        $this->kty = $data['kty'];
        $this->alg = $data['alg'];
        $this->use = $data['use'];
        $this->kid = $data['kid'];
        $this->n = $data['n'];
        $this->e = $data['e'];
    }

    public function toArray(): array
    {
        return [
            'kty' => $this->kty,
            'alg' => $this->alg,
            'use' => $this->use,
            'kid' => $this->kid,
            'n' => $this->n,
            'e' => $this->e,
        ];
    }
}
