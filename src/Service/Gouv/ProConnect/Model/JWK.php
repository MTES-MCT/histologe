<?php

namespace App\Service\Gouv\ProConnect\Model;

/**
 * Représente une clé publique au format JWK (JSON Web Key), utilisée pour vérifier les signatures des tokens JWT.
 *
 * Ce format est un standard défini par la spécification RFC 7517, couramment utilisé dans le protocole OpenID Connect (OIDC).
 * Les champs "n" (modulus) et "e" (exposant) définissent une clé RSA publique.
 *
 * @see https://datatracker.ietf.org/doc/html/rfc7517
 * @see https://openid.net/specs/openid-connect-core-1_0.html#Signing
 */
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
        $this->kty = $data['kty'] ?? null;
        $this->alg = $data['alg'] ?? null;
        $this->use = $data['use'] ?? null;
        $this->kid = $data['kid'] ?? null;
        $this->n = $data['n'] ?? null;
        $this->e = $data['e'] ?? null;
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
