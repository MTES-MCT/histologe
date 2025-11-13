<?php

namespace App\Service\Gouv\ProConnect;

use App\Service\Gouv\ProConnect\Response\JWKSResponse;
use CoderCat\JWKToPEM\JWKConverter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\Validation\Constraint\HasClaimWithValue;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;
use Psr\Log\LoggerInterface;

class ProConnectJwtValidator
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /** @param non-empty-string $idToken */
    public function validate(JWKSResponse $jwks, string $idToken, string $expectedNonce): bool
    {
        try {
            $publicKey = $jwks->findPublicKey();
            if (null === $publicKey) {
                return false;
            }

            /** @var non-empty-string $publicKeyPem */
            $publicKeyPem = (new JWKConverter())->toPEM($publicKey->toArray());

            $token = (new Parser(new JoseEncoder()))->parse($idToken);
            $validator = new Validator();

            $constraints = [
                new SignedWith(new Sha256(), InMemory::plainText($publicKeyPem)),
                new HasClaimWithValue('nonce', $expectedNonce),
            ];

            $validator->assert($token, ...$constraints);

            return true;
        } catch (\Throwable $exception) {
            $this->logger->error('JWT validation failed', ['error_message' => $exception->getMessage()]);

            return false;
        }
    }
}
